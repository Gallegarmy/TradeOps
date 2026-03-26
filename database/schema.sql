CREATE DATABASE IF NOT EXISTS trueque CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trueque;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE swap_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    have_text TEXT NOT NULL,
    want_text TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_swap_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_swap_posts_user (user_id),
    INDEX idx_swap_posts_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE swap_offers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    owner_user_id INT UNSIGNED NOT NULL,
    requester_user_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    accepted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_swap_offers_post FOREIGN KEY (post_id) REFERENCES swap_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_swap_offers_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_swap_offers_requester FOREIGN KEY (requester_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_swap_offers_owner (owner_user_id),
    INDEX idx_swap_offers_requester (requester_user_id),
    INDEX idx_swap_offers_status (status)
) ENGINE=InnoDB;

CREATE TABLE swap_completions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offer_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_swap_completions_offer FOREIGN KEY (offer_id) REFERENCES swap_offers(id) ON DELETE CASCADE,
    CONSTRAINT fk_swap_completions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_swap_completions_offer_user (offer_id, user_id),
    INDEX idx_swap_completions_offer (offer_id)
) ENGINE=InnoDB;

CREATE TABLE swap_ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offer_id INT UNSIGNED NOT NULL,
    rater_user_id INT UNSIGNED NOT NULL,
    rated_user_id INT UNSIGNED NOT NULL,
    rating ENUM('positive', 'negative') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_swap_ratings_offer FOREIGN KEY (offer_id) REFERENCES swap_offers(id) ON DELETE CASCADE,
    CONSTRAINT fk_swap_ratings_rater FOREIGN KEY (rater_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_swap_ratings_rated FOREIGN KEY (rated_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_swap_ratings_offer_rater (offer_id, rater_user_id),
    INDEX idx_swap_ratings_rated_user (rated_user_id),
    INDEX idx_swap_ratings_rating (rating)
) ENGINE=InnoDB;

-- Seed opcional para pruebas manuales:
-- INSERT INTO users (username, password_hash) VALUES
-- ('ana', '$2y$10$VxW4j5INzjCfA8jWeI8kV.GqjQoZX6mE6xPD58Ll35H5TADaBrZE6'), -- pass: secret123
-- ('luis', '$2y$10$VxW4j5INzjCfA8jWeI8kV.GqjQoZX6mE6xPD58Ll35H5TADaBrZE6');
