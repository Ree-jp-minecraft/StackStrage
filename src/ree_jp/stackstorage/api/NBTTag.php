<?php

namespace ree_jp\stackstorage\api;

enum NBTTag: string
{
    case Compound = "compound";
    case List = "list";
    case ByteArray = "byte_array";
    case Byte = "byte";
    case Double = "double";
    case Float = "float";
    case IntArray = "int_array";
    case Int = "int";
    case Long = "long";
    case Short = "short";
    case String = "string";
}
