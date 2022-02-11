-- #!mysql
-- #{ StackStorage
-- #    { init
-- #        { table
CREATE TABLE IF NOT EXISTS StackStorage
(
    xuid  BIGINT           NOT NULL,
    item  JSON             NOT NULL,
    count INTEGER UNSIGNED NOT NULL
);
-- #        }
-- #        { function
-- #            { create
CREATE PROCEDURE add_count(IN _xuid BIGINT, IN _item JSON, IN _count INT)
BEGIN
    SET @int_value = NULL;
    SELECT count
    INTO @int_value
    FROM StackStorage
    WHERE xuid = _xuid
      AND item = _item;
    if @int_value IS NULL
    then
        if _count > 0
        then
            INSERT INTO StackStorage
            VALUES (_xuid, _item, _count);
        end if;
    else
        SET @last_count = _count + cast(@int_value as INTEGER);
        if @last_count > 0
        then
            UPDATE StackStorage
            SET count = @last_count
            WHERE xuid = _xuid
              AND item = _item;
        else
            DELETE
            FROM StackStorage
            WHERE xuid = _xuid
              AND item = _item;
        end if;
    end if;
END;
-- #                }
-- #                { drop
DROP PROCEDURE IF EXISTS add_count;
-- #                }
-- #        }
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
-- #    { add
-- #    :xuid int
-- #    :item string
-- #    :count int
CALL add_count(:xuid, :item, :count);
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
