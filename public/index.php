<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = current_path();

if ($path === '/' && $method === 'GET') {
    $search = trim((string) ($_GET['q'] ?? ''));
    $posts = $repo->listPosts($search);
    $myOffers = [];
    if (current_user_id() !== null) {
        $myOffers = $repo->listOffersForUser(current_user_id());
    }

    render('home', [
        'posts' => $posts,
        'search' => $search,
        'myOffers' => $myOffers,
    ]);
    exit;
}

if ($path === '/register' && $method === 'GET') {
    render('register');
    exit;
}

if ($path === '/register' && $method === 'POST') {
    if (!validate_csrf()) {
        flash('danger', 'CSRF inválido.');
        redirect('/register');
    }

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        flash('danger', 'Usuario y contraseña son obligatorios.');
        redirect('/register');
    }

    if (strlen($username) < 3 || strlen($username) > 50) {
        flash('danger', 'El usuario debe tener entre 3 y 50 caracteres.');
        redirect('/register');
    }

    if (strlen($password) < 6) {
        flash('danger', 'La contraseña debe tener al menos 6 caracteres.');
        redirect('/register');
    }

    if ($repo->findUserByUsername($username) !== null) {
        flash('danger', 'Ese usuario ya existe.');
        redirect('/register');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    if ($hash === false) {
        flash('danger', 'No se pudo procesar la contraseña.');
        redirect('/register');
    }

    $repo->createUser($username, $hash);
    flash('success', 'Usuario creado. Ya puedes iniciar sesión.');
    redirect('/login');
}

if ($path === '/login' && $method === 'GET') {
    render('login');
    exit;
}

if ($path === '/login' && $method === 'POST') {
    if (!validate_csrf()) {
        flash('danger', 'CSRF inválido.');
        redirect('/login');
    }

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $user = $repo->findUserByUsername($username);

    if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
        flash('danger', 'Credenciales incorrectas.');
        redirect('/login');
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => (string) $user['username'],
    ];

    flash('success', 'Sesión iniciada.');
    redirect('/');
}

if ($path === '/logout' && $method === 'POST') {
    if (!validate_csrf()) {
        flash('danger', 'CSRF inválido.');
        redirect('/');
    }

    unset($_SESSION['user']);
    flash('success', 'Sesión cerrada.');
    redirect('/');
}

if ($path === '/swaps/new' && $method === 'GET') {
    require_login();
    render('new_swap');
    exit;
}

if ($path === '/swaps/new' && $method === 'POST') {
    require_login();

    if (!validate_csrf()) {
        flash('danger', 'CSRF inválido.');
        redirect('/swaps/new');
    }

    $haveText = trim((string) ($_POST['have_text'] ?? ''));
    $wantText = trim((string) ($_POST['want_text'] ?? ''));

    if ($haveText === '' || $wantText === '') {
        flash('danger', 'Debes rellenar qué tienes y qué buscas.');
        redirect('/swaps/new');
    }

    $repo->createPost((int) current_user_id(), $haveText, $wantText);
    flash('success', 'Trueque publicado.');
    redirect('/');
}

if ($path === '/offers/create' && $method === 'POST') {
    require_login();

    if (!validate_csrf()) {
        flash('danger', 'CSRF inválido.');
        redirect('/');
    }

    $postId = (int) ($_POST['post_id'] ?? 0);
    $post = $repo->findPostById($postId);
    $userId = (int) current_user_id();

    if ($post === null || (int) $post['is_active'] !== 1) {
        flash('danger', 'Publicación no disponible.');
        redirect('/');
    }

    if ((int) $post['user_id'] === $userId) {
        flash('warning', 'No puedes solicitar tu propio trueque.');
        redirect('/');
    }

    if ($repo->hasPendingOffer($postId, $userId)) {
        flash('warning', 'Ya tienes una solicitud pendiente para este trueque.');
        redirect('/');
    }

    $repo->createOffer($postId, (int) $post['user_id'], $userId);
    flash('success', 'Solicitud enviada.');
    redirect('/');
}

$acceptOrReject = [];
if (preg_match('#^/offers/(\d+)/(accept|reject)$#', $path, $acceptOrReject) === 1 && $method === 'POST') {
    require_login();

    if (!validate_csrf()) {
        flash('danger', 'CSRF inválido.');
        redirect('/');
    }

    $offerId = (int) $acceptOrReject[1];
    $action = $acceptOrReject[2];

    $offer = $repo->findOfferById($offerId);
    $userId = (int) current_user_id();

    if ($offer === null) {
        flash('danger', 'Solicitud no encontrada.');
        redirect('/');
    }

    if ((int) $offer['owner_user_id'] !== $userId) {
        flash('danger', 'No tienes permisos para esta acción.');
        redirect('/');
    }

    if ($offer['status'] !== 'pending') {
        flash('warning', 'Esta solicitud ya fue procesada.');
        redirect('/');
    }

    $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
    $repo->updateOfferStatus($offerId, $newStatus);

    flash('success', $newStatus === 'accepted' ? 'Solicitud aceptada.' : 'Solicitud rechazada.');
    redirect('/');
}

$completeMatch = [];
if (preg_match('#^/offers/(\d+)/complete$#', $path, $completeMatch) === 1 && $method === 'POST') {
    require_login();

    if (!validate_csrf()) {
        flash('danger', 'CSRF inválido.');
        redirect('/');
    }

    $offerId = (int) $completeMatch[1];
    $offer = $repo->findOfferById($offerId);
    $userId = (int) current_user_id();

    if ($offer === null) {
        flash('danger', 'Solicitud no encontrada.');
        redirect('/');
    }

    $isParticipant = in_array($userId, [(int) $offer['owner_user_id'], (int) $offer['requester_user_id']], true);
    if (!$isParticipant) {
        flash('danger', 'No puedes completar este trueque.');
        redirect('/');
    }

    if ($offer['status'] !== 'accepted') {
        flash('warning', 'Solo se puede completar un trueque aceptado.');
        redirect('/');
    }

    if ($repo->hasUserCompleted($offerId, $userId)) {
        flash('info', 'Ya marcaste este trueque como completado.');
        redirect('/');
    }

    $repo->markCompletion($offerId, $userId);

    if ($repo->getCompletionCount($offerId) >= 2) {
        flash('success', 'Ambos habéis confirmado. Ya podéis valorar.');
    } else {
        flash('success', 'Has marcado el trueque como completado. Falta la otra parte.');
    }

    redirect('/');
}

$rateMatch = [];
if (preg_match('#^/offers/(\d+)/rate$#', $path, $rateMatch) === 1 && $method === 'POST') {
    require_login();

    if (!validate_csrf()) {
        flash('danger', 'CSRF inválido.');
        redirect('/');
    }

    $offerId = (int) $rateMatch[1];
    $rating = (string) ($_POST['rating'] ?? '');
    $allowedRatings = ['positive', 'negative'];
    $userId = (int) current_user_id();

    if (!in_array($rating, $allowedRatings, true)) {
        flash('danger', 'Valoración inválida.');
        redirect('/');
    }

    $offer = $repo->findOfferById($offerId);
    if ($offer === null) {
        flash('danger', 'Solicitud no encontrada.');
        redirect('/');
    }

    $isParticipant = in_array($userId, [(int) $offer['owner_user_id'], (int) $offer['requester_user_id']], true);
    if (!$isParticipant) {
        flash('danger', 'No puedes valorar este trueque.');
        redirect('/');
    }

    if ($offer['status'] !== 'accepted' || $repo->getCompletionCount($offerId) < 2) {
        flash('warning', 'La valoración solo se habilita tras doble confirmación.');
        redirect('/');
    }

    if ($repo->hasUserRated($offerId, $userId)) {
        flash('info', 'Ya has valorado este trueque.');
        redirect('/');
    }

    $ratedUserId = $userId === (int) $offer['owner_user_id']
        ? (int) $offer['requester_user_id']
        : (int) $offer['owner_user_id'];

    $repo->createRating($offerId, $userId, $ratedUserId, $rating);
    flash('success', 'Valoración guardada.');
    redirect('/');
}

http_response_code(404);
render('not_found');
