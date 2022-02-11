-- #!sqlite
-- #{ StackStorage
-- #    { init.table
CREATE TABLE IF NOT EXISTS StackStorage
(
    xuid  BIGINT           NOT NULL,
    item  JSON             NOT NULL,
    count INTEGER UNSIGNED NOT NULL
);
-- #    }
-- #    { get_user
SELECT xuid
FROM StackStorage
GROUP BY xuid;
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
VALUES (:xuid, :item, :count);
-- #    }
-- #    { update
-- #    :xuid int
-- #    :item string
-- #    :count int
UPDATE StackStorage
SET count = :count
WHERE xuid = :xuid
  AND item = :item;
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
