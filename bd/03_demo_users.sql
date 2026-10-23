-- Demo users (one per role). Passwords are listed in the README.
INSERT IGNORE INTO `users`
  (`name`, `email`, `password_hash`, `role`, `created_at`, `updated_at`) VALUES
  ('Administrador Demo', 'admin@suplos.test', '$argon2id$v=19$m=65536,t=4,p=1$QWZ3VVphcFZBRHpvbHRJeg$4Rq31LWao449Q01vcC2jJ0KQLKDCsY1WAIiu2iUNTxk', 'admin', NOW(), NOW()),
  ('Editor Demo', 'editor@suplos.test', '$argon2id$v=19$m=65536,t=4,p=1$Z3RrL1VWdGVRMTIwSmJiSw$+Y/yaJ3sXY8yzA2Pcqw12mRXfXxRTG0IYvfTFtBl95s', 'editor', NOW(), NOW()),
  ('Lector Demo', 'lector@suplos.test', '$argon2id$v=19$m=65536,t=4,p=1$M2Z1ZnNiOWZIdy9jRy45bA$7l9DKPtdTXlYhogdYPdMev7+NUEFe6YsfDjJASwwjSE', 'viewer', NOW(), NOW());
