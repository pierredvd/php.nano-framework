DROP DATABASE IF EXISTS nano;
CREATE DATABASE nano;
DROP OWNED BY app;
\c nano;

DROP USER IF EXISTS app;
CREATE USER app WITH PASSWORD '96sd+hd5s';
ALTER DATABASE nano OWNER TO postgres;
GRANT ALL PRIVILEGES ON DATABASE nano TO postgres;
GRANT ALL ON ALL TABLES IN SCHEMA public TO postgres;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO postgres;
GRANT ALL PRIVILEGES ON DATABASE nano TO app;
GRANT ALL ON ALL TABLES IN SCHEMA public TO app;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO app;

DROP TRIGGER    IF EXISTS users_insert ON users;
DROP TRIGGER    IF EXISTS users_update ON users;
DROP FUNCTION   IF EXISTS users_insert();
DROP FUNCTION   IF EXISTS users_update();
DROP TABLE      IF EXISTS users CASCADE;

CREATE TABLE IF NOT EXISTS users(
    userid      INTEGER PRIMARY KEY,
    login       character varying(64) NOT NULL,
    password    character varying(64) NOT NULL,
    session     character(40) NULL,
    adminlevel  smallint not null default 1,
    name        character varying(64) NULL default NULL,
    lastname    character varying(64) NULL default NULL,
    email       character varying(256) NOT NULL,
    lostpasswordtoken character varying(32) NULL default NULL,
    createdat   timestamp,
    updatedat  timestamp
);

CREATE FUNCTION users_insert() RETURNS trigger AS $$
    DECLARE
        uniquelogin BOOLEAN;
        uniqueemail BOOLEAN;
    BEGIN
        SELECT COUNT(userid)=0 into uniquelogin FROM users WHERE login=NEW.login;
        IF not uniquelogin THEN
            RAISE EXCEPTION 'login already in use';
        END IF;
        SELECT COUNT(userid)=0 into uniqueemail FROM users WHERE email=NEW.email;
        IF not uniqueemail THEN
            RAISE EXCEPTION 'email already in use';
        END IF;
        SELECT CASE WHEN MAX(userid) IS NULL THEN 1 ELSE MAX(userid)+1 END into NEW.userid FROM users;
        NEW.createdat  := current_timestamp;
        NEW.updatedat := current_timestamp;
        RETURN NEW;
    END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER users_insert BEFORE INSERT ON users FOR EACH ROW EXECUTE PROCEDURE users_insert();

CREATE FUNCTION users_update() RETURNS trigger AS $$
    DECLARE
        uniquelogin     BOOLEAN;
        uniqueemail     BOOLEAN;
    BEGIN
        SELECT COUNT(userid)=0 into uniquelogin FROM users WHERE login=NEW.login AND userid!=OLD.userid;
        IF not uniquelogin THEN
            RAISE EXCEPTION 'login already in use';
        END IF;
        SELECT COUNT(userid)=0 into uniqueemail FROM users WHERE email=NEW.email AND userid!=OLD.userid;
        IF not uniqueemail THEN
            RAISE EXCEPTION 'email already in use';
        END IF;
        NEW.updatedat := current_timestamp;
        RETURN NEW;
    END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER users_update BEFORE UPDATE ON users FOR EACH ROW EXECUTE PROCEDURE users_update();

INSERT INTO users(login, password, adminlevel, name, lastname, email) VALUES
('admin', '705174cf51135982bfbe26734723a49c893612e3', 2, 'Administrateur', '', 'pierre_dvd@msn.com'),
('user' , '705174cf51135982bfbe26734723a49c893612e3', 1, 'Utilisateur1'  , '', 'user@junk.com');