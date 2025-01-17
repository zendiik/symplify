<?php

declare(strict_types=1);

namespace Symplify\EasyCI\StaticDetector\Collector;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use Symplify\EasyCI\StaticDetector\ValueObject\StaticClassMethod;
use Symplify\EasyCI\StaticDetector\ValueObject\StaticClassMethodWithStaticCalls;
use Symplify\EasyCI\StaticDetector\ValueObject\StaticReport;
use Symplify\SymplifyKernel\Exception\ShouldNotHappenException;

final class StaticNodeCollector
{
    /**
     * @var StaticClassMethod[]
     */
    private array $staticClassMethods = [];

    /**
     * @var array<string, array<string, StaticCall[]>>
     */
    private array $staticCalls = [];

    public function addStaticClassMethod(ClassMethod $classMethod, ClassLike $classLike): void
    {
        if (! $classLike->namespacedName instanceof Name) {
            return;
        }

        $className = $classLike->namespacedName->toString();
        $methodName = $classMethod->name->toString();

        $this->staticClassMethods[] = new StaticClassMethod($className, $methodName, $classMethod);
    }

    public function addStaticCall(StaticCall $staticCall): void
    {
        if ($staticCall->class instanceof Expr) {
            // weird expression, skip
            return;
        }

        if ($staticCall->name instanceof Expr) {
            // weird expression, skip
            return;
        }

        $class = (string) $staticCall->class;
        $method = (string) $staticCall->name;
        $this->staticCalls[$class][$method][] = $staticCall;
    }

    public function addStaticCallInsideClass(StaticCall $staticCall, ClassLike $classLike): void
    {
        if ($staticCall->class instanceof Expr) {
            // weird expression, skip
            return;
        }

        if ($staticCall->name instanceof Expr) {
            // weird expression, skip
            return;
        }

        $class = $this->resolveClass($staticCall->class, $classLike);
        $method = (string) $staticCall->name;
        $this->staticCalls[$class][$method][] = $staticCall;
    }

    public function generateStaticReport(): StaticReport
    {
        return new StaticReport($this->getStaticClassMethodWithStaticCalls());
    }

    /**
     * @return StaticClassMethodWithStaticCalls[]
     */
    private function getStaticClassMethodWithStaticCalls(): array
    {
        $staticClassMethodWithStaticCalls = [];

        foreach ($this->staticClassMethods as $staticClassMethod) {
            $staticCalls = $this->staticCalls[$staticClassMethod->getClass()][$staticClassMethod->getMethod()] ?? [];

            $staticClassMethodWithStaticCalls[] = new StaticClassMethodWithStaticCalls(
                $staticClassMethod,
                $staticCalls
            );
        }

        return $staticClassMethodWithStaticCalls;
    }

    private function resolveClass(Name $staticClassName, ClassLike $classLike): string
    {
        $class = (string) $staticClassName;
        if (in_array($class, ['self', 'static'], true) && $classLike->namespacedName instanceof Name) {
            return $classLike->namespacedName->toString();
        }

        if ($class === 'parent') {
            if (! $classLike instanceof Class_) {
                throw new ShouldNotHappenException();
            }

            if ($classLike->extends === null) {
                throw new ShouldNotHappenException();
            }

            return (string) $classLike->extends;
        }

        return $class;
    }
}
