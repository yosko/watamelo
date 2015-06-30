
----
-- Tables
----
DROP TABLE IF EXISTS "wa_user_level";
CREATE TABLE wa_user_level (
    'id'                INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    'name'              VARCHAR(256),
    'level'             INT NOT NULL
);
INSERT INTO "wa_user_level" ("id","name","level") VALUES ('1','admin','2');
INSERT INTO "wa_user_level" ("id","name","level") VALUES ('2','user','1');
INSERT INTO "wa_user_level" ("id","name","level") VALUES ('3','visitor','0');
DROP TABLE IF EXISTS "wa_user";
CREATE TABLE wa_user (
    'id'                INTEGER NULL PRIMARY KEY AUTOINCREMENT,
    'level'             INT NOT NULL DEFAULT 1,
    'login'             TEXT,
    'password'          TEXT,
    'creation'          DATETIME NOT NULL DEFAULT current_timestamp,
    'bio'               TEXT
);
INSERT INTO "wa_user" ("id","level","login","password","creation","bio") VALUES ('1','2','admin','$2y$10$DM3DucHoLtyP08CU1h95WOwVlwYkWtP4ITzdC4y3YGzr2/VxH9PGC','Application administrator');

----
-- Indexes
----


----
-- Triggers
----
