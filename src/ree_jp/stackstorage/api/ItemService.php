<?php

namespace ree_jp\stackstorage\api;

use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use UnhandledMatchError;

class ItemService
{
    public function nbtToJson(CompoundTag|ListTag $tags): array|false
    {
        $result = [];
        foreach ($tags as $key => $tag) {
            try {
                $result[$key] = match (true) {
                    $tag instanceof CompoundTag => ["nbt_tag" => NBTTag::Compound, "value" => $this->nbtToJson($tag)],
                    $tag instanceof ListTag => ["nbt_tag" => NBTTag::List, "value" => $this->nbtToJson($tag)],
                    $tag instanceof ByteArrayTag => ["nbt_tag" => NBTTag::ByteArray, "value" => $tag->getValue()],
                    $tag instanceof ByteTag => ["nbt_tag" => NBTTag::Byte, "value" => $tag->getValue()],
                    $tag instanceof DoubleTag => ["nbt_tag" => NBTTag::Double, "value" => $tag->getValue()],
                    $tag instanceof FloatTag => ["nbt_tag" => NBTTag::Float, "value" => $tag->getValue()],
                    $tag instanceof IntArrayTag => ["nbt_tag" => NBTTag::IntArray, "value" => $tag->getValue()],
                    $tag instanceof IntTag => ["nbt_tag" => NBTTag::Int, "value" => $tag->getValue()],
                    $tag instanceof LongTag => ["nbt_tag" => NBTTag::Long, "value" => $tag->getValue()],
                    $tag instanceof ShortTag => ["nbt_tag" => NBTTag::Short, "value" => $tag->getValue()],
                    $tag instanceof StringTag => ["nbt_tag" => NBTTag::String, "value" => $tag->getValue()],
                };
            } catch (UnhandledMatchError $error) {
                var_dump($error->getMessage());
                return false;
            }
        }
        return $result;
    }

    /**
     * @param array $tags
     * @return Tag[]|false
     */
    public function jsonToNbt(array $tags): array|false
    {
        $result = [];
        foreach ($tags as $key => $array) {
            try {
                $tag = match ($array["nbt_tag"]) {
                    NBTTag::Compound->value => $this->toCompoundTag($this->jsonToNbt($array["value"])),
                    NBTTag::List->value => new ListTag($this->jsonToNbt($array["value"])),
                    NBTTag::ByteArray->value => new ByteArrayTag($array["value"]),
                    NBTTag::Byte->value => new ByteTag($array["value"]),
                    NBTTag::Double->value => new DoubleTag($array["value"]),
                    NBTTag::Float->value => new FloatTag($array["value"]),
                    NBTTag::IntArray->value => new IntArrayTag($array["value"]),
                    NBTTag::Int->value => new IntTag($array["value"]),
                    NBTTag::Long->value => new LongTag($array["value"]),
                    NBTTag::Short->value => new ShortTag($array["value"]),
                    NBTTag::String->value => new StringTag($array["value"]),
                };
                $result[$key] = $tag;
            } catch (UnhandledMatchError $error) {
                var_dump($error->getMessage());
                return false;
            }
        }
        return $result;
    }

    public function toCompoundTag(array $array): CompoundTag
    {
        $compound = new CompoundTag();
        foreach ($array as $key => $tag) {
            $compound->setTag($key, $tag);
        }
        return $compound;
    }
}
