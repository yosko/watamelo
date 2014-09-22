
----
-- Tables
----
DROP TABLE IF EXISTS "wa_user_level";
CREATE TABLE wa_user_level (
    'userLevelId'       INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    'userLevelName'     VARCHAR(256),
    'userLevel'         INT NOT NULL
);
INSERT INTO "wa_user_level" ("userLevelId","userLevelName","userLevel") VALUES ('1','admin','2');
INSERT INTO "wa_user_level" ("userLevelId","userLevelName","userLevel") VALUES ('2','user','1');
INSERT INTO "wa_user_level" ("userLevelId","userLevelName","userLevel") VALUES ('3','visitor','0');
DROP TABLE IF EXISTS "wa_user";
CREATE TABLE wa_user (
    'userId'            INTEGER NULL PRIMARY KEY AUTOINCREMENT,
    'userLevel'         INT NOT NULL DEFAULT 1,
    'userLogin'          TEXT,
    'userPassword'      TEXT,
    'userCreation'      DATETIME NOT NULL DEFAULT current_timestamp,
    'userBio'           TEXT
);
INSERT INTO "wa_user" ("userId","userLevel","userLogin","userPassword","userCreation","userBio") VALUES ('1','2','admin','$2y$10$DM3DucHoLtyP08CU1h95WOnSC.T7HhGV2bUYh8ad/sDEdEGIt8Lc2','2012-11-21 12:05:37','Application administrator');

----
-- Indexes
----


----
-- Triggers
----
