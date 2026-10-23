-- Demo users (one per role). Passwords are listed in the README.
INSERT IGNORE INTO `users` (`name`, `email`, `password_hash`, `role`, `created_at`, `updated_at`) VALUES
  ('Administrador Demo', 'admin@suplos.test', '$argon2id$v=19$m=65536,t=4,p=1$REN2MlJoQWVWM3c2RnhyYw$m1Vk1R/0RL/5dvftgTdMvFjgJYkrvyqme0DEp2J/Ooo', 'admin', NOW(), NOW()),
  ('Editor Demo', 'editor@suplos.test', '$argon2id$v=19$m=65536,t=4,p=1$OWdnV1JnYnNJRTlQVVlBeA$bMWVECD2pStrnssz6xb3DvP15OwByVFTcLtEm/8F/J8', 'editor', NOW(), NOW()),
  ('Lector Demo', 'lector@suplos.test', '$argon2id$v=19$m=65536,t=4,p=1$Tk1ZZ0tudkM5UWtSdS96Ng$NNynPr2O8MUjXIXP6YgOYP5DXx2uv7CJnRcK2O9Oq0o', 'viewer', NOW(), NOW());
