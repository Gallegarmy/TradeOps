<?php

declare(strict_types=1);

final class Repository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findUserByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, password_hash, created_at FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function createUser(string $username, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)');

        return $stmt->execute([
            'username' => $username,
            'password_hash' => $passwordHash,
        ]);
    }

    public function listPosts(string $query = ''): array
    {
        $sql = 'SELECT p.id, p.have_text, p.want_text, p.created_at, u.id AS user_id, u.username,
                       COALESCE(SUM(CASE WHEN r.rating = "positive" THEN 1 ELSE 0 END), 0) AS positive_count,
                       COALESCE(SUM(CASE WHEN r.rating = "negative" THEN 1 ELSE 0 END), 0) AS negative_count
                FROM swap_posts p
                INNER JOIN users u ON u.id = p.user_id
                LEFT JOIN swap_ratings r ON r.rated_user_id = u.id
                WHERE p.is_active = 1';
        $params = [];

        if ($query !== '') {
            $sql .= ' AND (p.have_text LIKE :q OR p.want_text LIKE :q OR u.username LIKE :q)';
            $params['q'] = '%' . $query . '%';
        }

        $sql .= ' GROUP BY p.id, p.have_text, p.want_text, p.created_at, u.id, u.username
                  ORDER BY p.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function createPost(int $userId, string $haveText, string $wantText): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO swap_posts (user_id, have_text, want_text) VALUES (:user_id, :have_text, :want_text)');

        return $stmt->execute([
            'user_id' => $userId,
            'have_text' => $haveText,
            'want_text' => $wantText,
        ]);
    }

    public function findPostById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, user_id, have_text, want_text, is_active FROM swap_posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $post = $stmt->fetch();

        return $post ?: null;
    }

    public function createOffer(int $postId, int $ownerId, int $requesterId): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO swap_offers (post_id, owner_user_id, requester_user_id, status) VALUES (:post_id, :owner_user_id, :requester_user_id, "pending")');

        return $stmt->execute([
            'post_id' => $postId,
            'owner_user_id' => $ownerId,
            'requester_user_id' => $requesterId,
        ]);
    }

    public function hasPendingOffer(int $postId, int $requesterId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM swap_offers WHERE post_id = :post_id AND requester_user_id = :requester_user_id AND status = "pending" LIMIT 1');
        $stmt->execute([
            'post_id' => $postId,
            'requester_user_id' => $requesterId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function listOffersForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT o.id, o.post_id, o.owner_user_id, o.requester_user_id, o.status, o.created_at, o.accepted_at,
                                            p.have_text, p.want_text,
                                            owner.username AS owner_username,
                                            requester.username AS requester_username,
                                            (SELECT COUNT(*) FROM swap_completions c WHERE c.offer_id = o.id) AS completion_count,
                                            EXISTS(SELECT 1 FROM swap_completions c1 WHERE c1.offer_id = o.id AND c1.user_id = :uid_completion) AS completed_by_me,
                                            EXISTS(SELECT 1 FROM swap_ratings sr WHERE sr.offer_id = o.id AND sr.rater_user_id = :uid_rating) AS rated_by_me
                                     FROM swap_offers o
                                     INNER JOIN swap_posts p ON p.id = o.post_id
                                     INNER JOIN users owner ON owner.id = o.owner_user_id
                                     INNER JOIN users requester ON requester.id = o.requester_user_id
                                     WHERE o.owner_user_id = :uid_owner OR o.requester_user_id = :uid_requester
                                     ORDER BY o.created_at DESC');
        $stmt->execute([
            'uid_completion' => $userId,
            'uid_rating' => $userId,
            'uid_owner' => $userId,
            'uid_requester' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    public function findOfferById(int $offerId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, post_id, owner_user_id, requester_user_id, status FROM swap_offers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $offerId]);
        $offer = $stmt->fetch();

        return $offer ?: null;
    }

    public function updateOfferStatus(int $offerId, string $status): bool
    {
        $sql = 'UPDATE swap_offers SET status = :status';
        if ($status === 'accepted') {
            $sql .= ', accepted_at = NOW()';
        }
        $sql .= ' WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'status' => $status,
            'id' => $offerId,
        ]);
    }

    public function markCompletion(int $offerId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO swap_completions (offer_id, user_id) VALUES (:offer_id, :user_id)');

        return $stmt->execute([
            'offer_id' => $offerId,
            'user_id' => $userId,
        ]);
    }

    public function getCompletionCount(int $offerId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM swap_completions WHERE offer_id = :offer_id');
        $stmt->execute(['offer_id' => $offerId]);

        return (int) $stmt->fetchColumn();
    }

    public function hasUserCompleted(int $offerId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM swap_completions WHERE offer_id = :offer_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            'offer_id' => $offerId,
            'user_id' => $userId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function hasUserRated(int $offerId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM swap_ratings WHERE offer_id = :offer_id AND rater_user_id = :user_id LIMIT 1');
        $stmt->execute([
            'offer_id' => $offerId,
            'user_id' => $userId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function createRating(int $offerId, int $raterUserId, int $ratedUserId, string $rating): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO swap_ratings (offer_id, rater_user_id, rated_user_id, rating) VALUES (:offer_id, :rater_user_id, :rated_user_id, :rating)');

        return $stmt->execute([
            'offer_id' => $offerId,
            'rater_user_id' => $raterUserId,
            'rated_user_id' => $ratedUserId,
            'rating' => $rating,
        ]);
    }
}
