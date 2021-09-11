-- #!mysql
-- #{ StackStorage
-- #    { init
CREATE TABLE IF NOT EXISTS StackStorage
(
    xuid  BIGINT PRIMARY KEY NOT NULL,
    item  JSON               NOT NULL,
    count INTEGER UNSIGNED   NOT NULL
);
-- #    }
-- #    { get_all
-- #    :xuid int
SELECT item, count
FROM StackStorage
WHERE xuid = :xuid;
-- #    }
-- #    { get
-- #    :xuid int
-- #    :item string
SELECT count
FROM StackStorage
WHERE xuid = :xuid
  AND item = :item;
-- #    }
-- #    { set
-- #    :xuid int
-- #    :item string
-- #    :count int
INSERT INTO StackStorage
VALUES (:xuid, :item, :count)
ON DUPLICATE KEY UPDATE count = :count;
-- #    }
-- #    { delete
-- #    :xuid int
-- #    :item string
DELETE
FROM StackStorage
WHERE xuid = :xuid
  AND item = :item;
-- #    }
-- #}
