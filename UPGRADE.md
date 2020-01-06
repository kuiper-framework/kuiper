# 升级说明

## kuiper/reflection

ReflectionType::parse() 使用 TypeUtils::parse() 代替。
ReflectionType 以下方法使用 TypeUtils 中相应方法代替 :

- isArray
- isCompound 使用 isComposite 代替
- isBuiltin 使用 isScalar 代替
- isNull 
- isNullable 
- isMixed 使用 instanceof MixedType
- isObject 使用 instanceof ObjectType 代替
- validate
- sanitize

