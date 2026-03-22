CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    username VARCHAR(20) NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guest_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    guest_token VARCHAR(128) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_guest_sessions_token (guest_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS magic_login_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    request_ip VARCHAR(45) NULL DEFAULT NULL,
    user_agent VARCHAR(255) NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_magic_login_email_created (email, created_at),
    KEY idx_magic_login_hash_expires (token_hash, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    session_token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_sessions_hash (session_token_hash),
    KEY idx_user_sessions_user_id (user_id),
    KEY idx_user_sessions_expires (expires_at),
    CONSTRAINT fk_user_sessions_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL DEFAULT NULL,
    slug VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_user_id (user_id),
    CONSTRAINT fk_categories_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS todos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL DEFAULT NULL,
    guest_session_id BIGINT UNSIGNED NULL DEFAULT NULL,
    title VARCHAR(120) NOT NULL,
    description TEXT NULL DEFAULT NULL,
    category_id BIGINT UNSIGNED NULL DEFAULT NULL,
    priority VARCHAR(10) NOT NULL DEFAULT 'medium',
    due_date DATE NULL DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    is_doing TINYINT(1) NOT NULL DEFAULT 0,
    done_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_todos_user_id (user_id),
    KEY idx_todos_guest_session_id (guest_session_id),
    KEY idx_todos_due_date (due_date),
    KEY idx_todos_is_done (is_done),
    KEY idx_todos_done_at (done_at),
    KEY idx_todos_done_sort_order (is_done, sort_order),
    KEY idx_todos_deleted_at (deleted_at),
    KEY idx_todos_category_id (category_id),
    CONSTRAINT fk_todos_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL,
    CONSTRAINT fk_todos_guest_session
        FOREIGN KEY (guest_session_id) REFERENCES guest_sessions (id)
        ON DELETE SET NULL,
    CONSTRAINT fk_todos_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS feed_posts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    post_date DATE NOT NULL,
    post_sequence TINYINT UNSIGNED NOT NULL DEFAULT 1,
    completed_count INT NOT NULL,
    category_summary VARCHAR(255) NULL DEFAULT NULL,
    auto_summary VARCHAR(255) NOT NULL,
    template_lines_json JSON NOT NULL,
    icon_key VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public_until DATETIME NOT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_feed_posts_user_date_sequence (user_id, post_date, post_sequence),
    KEY idx_feed_posts_user_date (user_id, post_date),
    KEY idx_feed_posts_public_until (public_until),
    KEY idx_feed_posts_created_at (created_at),
    KEY idx_feed_posts_deleted_at (deleted_at),
    CONSTRAINT fk_feed_posts_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS feed_post_likes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    feed_post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_feed_post_likes_post_user (feed_post_id, user_id),
    KEY idx_feed_post_likes_user_id (user_id),
    CONSTRAINT fk_feed_post_likes_post
        FOREIGN KEY (feed_post_id) REFERENCES feed_posts (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_feed_post_likes_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (user_id, slug, name, color, sort_order) VALUES
    (NULL, 'work', '仕事', '#6f4abf', 10),
    (NULL, 'personal', 'プライベート', '#b66ad8', 20),
    (NULL, 'health', '健康', '#6f93d6', 30),
    (NULL, 'learning', '学び', '#6da88f', 40),
    (NULL, 'housework', '家事', '#d69a6f', 50),
    (NULL, 'money', 'お金', '#c28a5b', 60),
    (NULL, 'other', 'その他', '#9e9aa8', 70)
ON DUPLICATE KEY UPDATE
    user_id = VALUES(user_id),
    name = VALUES(name),
    color = VALUES(color),
    sort_order = VALUES(sort_order);
