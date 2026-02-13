CREATE TABLE IF NOT EXISTS `lliure_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL DEFAULT '',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário admin padrão (senha: admin123)
INSERT INTO `lliure_users` (`username`, `email`, `password_hash`, `name`, `active`)
VALUES ('admin', 'admin@lliure.local', '$2y$10$QAQpVuFdX38UrEuh710b..atUJfJnTeffZF1/S3Tg0q1QtkGVAEfC', 'Administrador', 1);
