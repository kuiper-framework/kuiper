# CHANGELOG

## v0.3.0

### kuiper\helper

- [BRK] Arrays::toArray 对于 isFoo, hasFoo 方法提取字段名改为 foo

### kuiper\reflection

- [ADD] ReflectionFile 添加方法 getTraits()
- [BRK] ReflectionType::parse() 改为使用 TypeUtils::parse() 创建。 ReflectionTypeInterface isArray 等方法使用 TypeUtils 中方法代替
- [FIX] 使用 use 引入命名空间， FqcnResolver::resolve() 解析类全名不正确
  
### kuiper\annotations

- [BRK] Annotation 类构造函数第二个参数改为反射对象
- [ADD] 支持 trait 注解

### kuiper\serializer

- [BRK] NormalizerInterface fromArray/toArray 改名为 normalize/denormalize

### kuiper\web

- [BRK] RouteRegistarInterface 重命名为 RouteRegistrarInterface

### kuiper\boot

- [BRK] container 不使用 CompositeContainer，不支持名字空间
- [ADD] MonologProvider 支持 logging 配置，可设置多个 logger
- [ADD] RpcClientProvider 支持服务分组功能，同组服务使用相同 endpoint
- [ADD] WebApplicationProvider 支持 app.routes 配置，扫描名字空间下所有 Controller
  配置路由
