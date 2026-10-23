-- Demo users (one per role). Passwords are listed in the README.
INSERT IGNORE INTO `users`
  (`name`, `email`, `password_hash`, `role`, `created_at`, `updated_at`) VALUES
  ('Administrador Demo', 'admin@licita.test', '$argon2id$v=19$m=65536,t=4,p=1$SmE2Z2t1U2dIbnZNNmRJWg$sNytBBRm/PzCBMjO2mWLULoivAKwSVMUQ1JS284d/Pk', 'admin', NOW(), NOW()),
  ('Editor Demo', 'editor@licita.test', '$argon2id$v=19$m=65536,t=4,p=1$NXIwYTRPakVNQTk1THVNdQ$LNGOBeppNiSgFkpnBeSKUmEMpvNALu10H1T1nCS7/jQ', 'editor', NOW(), NOW()),
  ('Lector Demo', 'lector@licita.test', '$argon2id$v=19$m=65536,t=4,p=1$T3F5S3ViUFBZTXFVcEtsRA$qicEW8zg4Toer0pPA+pmar9pc1Y8nvuvv7nkAayGE7k', 'viewer', NOW(), NOW());
