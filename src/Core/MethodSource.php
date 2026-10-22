<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Core;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionException;

/**
 * Locates the source of a method as PHP resolves it, including one inherited from a parent class or trait.
 *
 * Reflection decides which method a call reaches (case-insensitively, through parents and traits); the declaring
 * file is then parsed and the method at the reflected start line is taken, so another class in the same file, or a
 * similarly named method, can never stand in for it.
 */
final class MethodSource
{
    /** @var array<string, array<Node>> */
    private static array $parsed = [];

    private function __construct(
        public readonly ClassMethod $node,
        public readonly string $file,
        public readonly int $line,
    ) {}

    /**
     * The method's source, or null when the class has no such method or its source cannot be read.
     *
     * @param  ReflectionClass<object>  $class
     */
    public static function find(ReflectionClass $class, string $name): ?self
    {
        try {
            $method = $class->getMethod($name);
        } catch (ReflectionException) {
            return null;
        }

        $file = $method->getFileName();
        $line = $method->getStartLine();

        if ($file === false || $line === false) {
            return null;
        }

        /** @var array<int, ClassMethod> $candidates */
        $candidates = (new NodeFinder)->findInstanceOf(self::parse($file), ClassMethod::class);

        foreach ($candidates as $candidate) {
            if ($candidate->getStartLine() === $line && $candidate->name->toLowerString() === strtolower($name)) {
                return new self($candidate, $file, $line);
            }
        }

        return null;
    }

    /**
     * The nodes of a type in the method body, leaving out those inside nested closures, arrow functions, functions
     * and anonymous classes, which belong to a different scope.
     *
     * @template T of Node
     *
     * @param  class-string<T>  $type
     * @return list<T>
     */
    public function nodesOfType(string $type): array
    {
        $visitor = new class($type) extends NodeVisitorAbstract
        {
            /** @var list<Node> */
            public array $found = [];

            /** @param class-string<Node> $type */
            public function __construct(private readonly string $type) {}

            public function enterNode(Node $node): ?int
            {
                if ($node instanceof $this->type) {
                    $this->found[] = $node;
                }

                if ($node instanceof Closure || $node instanceof ArrowFunction || $node instanceof Function_ || $node instanceof ClassLike) {
                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }

                return null;
            }
        };

        new NodeTraverser($visitor)->traverse($this->node->stmts ?? []);

        /** @var list<T> $found */
        $found = $visitor->found;

        return $found;
    }

    /**
     * Parses PHP source with names resolved to their fully qualified form, or returns no nodes when it does not parse.
     *
     * @return array<Node>
     */
    public static function parseCode(string $code): array
    {
        try {
            $stmts = (new ParserFactory)->createForHostVersion()->parse($code);
        } catch (Error) {
            return [];
        }

        return new NodeTraverser(new NameResolver)->traverse($stmts ?? []);
    }

    /**
     * @return array<Node>
     */
    private static function parse(string $file): array
    {
        if (! isset(self::$parsed[$file])) {
            $code = @file_get_contents($file);

            self::$parsed[$file] = $code === false ? [] : self::parseCode($code);
        }

        return self::$parsed[$file];
    }
}
