# Kuiper Annotation

Kuiper Annotation 对 `\Doctrine\Common\Annotations\Reader` 进行封装，
在应用代码中使用 `\kuiper\annotations\AnnotationReaderInterface` 注入实例。
特别是在协程环境下，可以解决 Doctrine Reader 竞态冲突产生错误。