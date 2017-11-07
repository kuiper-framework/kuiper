<?php

namespace kuiper\reflection;

interface ReflectionFileInterface
{
    /**
     * Gets the file name.
     *
     * @return string
     */
    public function getFile(): string;

    /**
     * Gets all namespaces defined in the file.
     *
     * @return string[]
     *
     * @throws exception\SyntaxErrorException
     * @throws exception\FileNotFoundException
     */
    public function getNamespaces(): array;

    /**
     * Gets all classes defined in the file.
     *
     * @return string[]
     *
     * @throws exception\SyntaxErrorException
     * @throws exception\FileNotFoundException
     */
    public function getClasses(): array;

    /**
     * Gets all traits defined in the file.
     *
     * @return string[]
     *
     * @throws exception\SyntaxErrorException
     * @throws exception\FileNotFoundException
     */
    public function getTraits(): array;

    /**
     * Gets all imported classes in the namespace
     * return array key is alias, value is the Full Qualified Class Name.
     *
     * @param string $namespace
     *
     * @return array
     *
     * @throws exception\SyntaxErrorException
     * @throws exception\FileNotFoundException
     */
    public function getImportedClasses(string $namespace): array;

    /**
     * Gets all imported functions in the namespace
     * return array key is alias, value is the Full Qualified Function Name.
     *
     * @param string $namespace
     *
     * @return array
     *
     * @throws exception\SyntaxErrorException
     * @throws exception\FileNotFoundException
     */
    public function getImportedFunctions(string $namespace): array;

    /**
     * Gets all imported constants in the namespace
     * return array key is alias, value is the Full Qualified Constant Name.
     *
     * @param string $namespace
     *
     * @return array
     *
     * @throws exception\SyntaxErrorException
     * @throws exception\FileNotFoundException
     */
    public function getImportedConstants(string $namespace): array;
}
