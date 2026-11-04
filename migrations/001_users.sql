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

-- Nenhum usuário é criado aqui. Até a v2.0.0-alpha.1 esta migração criava o
-- usuário `admin` com uma senha fixa, conhecida por quem lesse este arquivo.
-- O primeiro usuário passa a ser criado pela tela de boas-vindas (welcome),
-- com a senha escolhida por quem instala.
--
-- Instalações antigas: se existir um `admin` que nunca teve a senha trocada,
-- troque-a (ou desative o usuário).
