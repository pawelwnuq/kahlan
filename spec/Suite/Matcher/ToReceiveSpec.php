<?php
namespace Kahlan\Spec\Suite\Matcher;

use Exception;
use InvalidArgumentException;
use DateTime;

use Kahlan\Jit\Interceptor;
use Kahlan\Arg;
use Kahlan\Plugin\Stub;
use Kahlan\Plugin\Monkey;
use Kahlan\Jit\Patcher\Pointcut as PointcutPatcher;
use Kahlan\Jit\Patcher\Monkey as MonkeyPatcher;
use Kahlan\Matcher\ToReceive;

use Kahlan\Spec\Fixture\Plugin\Pointcut\Foo;
use Kahlan\Spec\Fixture\Plugin\Pointcut\SubBar;
use Kahlan\Spec\Fixture\Plugin\Monkey\User;

describe("toReceive", function() {

    describe("::match()", function() {

        /**
         * Save current & reinitialize the Interceptor class.
         */
        before(function() {
            $this->previous = Interceptor::instance();
            Interceptor::unpatch();

            $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
            $include = ['Kahlan\Spec\\'];
            $interceptor = Interceptor::patch(compact('include', 'cachePath'));
            $interceptor->patchers()->add('pointcut', new PointcutPatcher());
            $interceptor->patchers()->add('monkey', new MonkeyPatcher());
        });

        /**
         * Restore Interceptor class.
         */
        after(function() {
            Interceptor::load($this->previous);
        });

        context("with dynamic call", function() {

            it("expects called method to be called", function() {

                $foo = new Foo();
                expect($foo)->toReceive('message');
                $foo->message();

            });

            it("expects uncalled method to be uncalled", function() {

                $foo = new Foo();
                expect($foo)->not->toReceive('message');

            });

            it("expects method called in the past to be uncalled", function() {

                $foo = new Foo();
                $foo->message();
                expect($foo)->not->toReceive('message');

            });

            it("expects static method called using non-static way to still called (PHP behavior)", function() {

                $foo = new Foo();
                expect($foo)->toReceive('::version');
                $foo->version();

            });

            it("expects static method called using non-static way to be not called on instance", function() {

                $foo = new Foo();
                expect($foo)->not->toReceive('version');
                $foo->version();

            });

            it("throw an exception when trying to play with core instance", function() {

                expect(function() {
                    $date = new DateTime();
                    expect($date)->toReceive('getTimestamp');
                })->toThrow(new InvalidArgumentException("Can't Spy/Stub instances not from PHP userland, use `Stub::create()` to proxify your instance first."));

            });

            context("when using with()", function() {

                it("expects called method to be called with correct arguments", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->with('My Message', 'My Other Message');
                    $foo->message('My Message', 'My Other Message');

                });

                it("expects called method with incorrect arguments to not be called", function() {

                    $foo = new Foo();
                    expect($foo)->not->toReceive('message')->with('My Message');
                    $foo->message('Incorrect Message');

                });

                it("expects called method with missing arguments to not be called", function() {

                    $foo = new Foo();
                    expect($foo)->not->toReceive('message')->with('My Message');
                    $foo->message();

                });

                it("expects arguments match the toContain argument matcher", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->with(Arg::toContain('My Message'));
                    $foo->message(['My Message', 'My Other Message']);

                });

                it("expects arguments match the argument matchers", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->with(Arg::toBeA('boolean'));
                    expect($foo)->toReceive('message')->with(Arg::toBeA('string'));
                    $foo->message(true);
                    $foo->message('Hello World');

                });

                it("expects arguments to not match the toContain argument matcher", function() {

                    $foo = new Foo();
                    expect($foo)->not->toReceive('message')->with(Arg::toContain('Message'));
                    $foo->message(['My Message', 'My Other Message']);

                });

            });

            context("when using times()", function() {

                it("expects called method to be called exactly once", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->once();
                    $foo->message();

                });

                it("expects called method to be called exactly a specified times", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->times(3);
                    $foo->message();
                    $foo->message();
                    $foo->message();

                });

                it("expects called method not called exactly a specified times to be uncalled", function() {

                    $foo = new Foo();
                    expect($foo)->not->toReceive('message')->times(1);
                    $foo->message();
                    $foo->message();

                });

            });

            context("when using classname", function() {

                it("expects called method to be called", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('message');
                    $foo->message();

                });

                it("expects method called in the past to be called", function() {

                    $foo = new Foo();
                    $foo->message();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('message');

                });

                it("expects uncalled method to be uncalled", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('message');
                });

                it("expects called method to be called exactly once", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('message')->once();
                    $foo->message();

                });

                it("expects called method to be called exactly a specified times", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('message')->times(3);
                    $foo->message();
                    $foo->message();
                    $foo->message();

                });

                it("expects called method not called exactly a specified times to be uncalled", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('message')->times(1);
                    $foo->message();
                    $foo->message();

                });

                it("expects uncalled method to be uncalled", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('message');

                });

                it("expects called method to be uncalled using a wrong classname", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\FooFoo')->not->toReceive('message');
                    $foo->message();

                });

                it("expects not overrided method to also be called on method's __CLASS__", function() {

                    $bar = new SubBar();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Bar')->toReceive('send');
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\SubBar')->toReceive('send');
                    $bar->send();

                });

                it("expects overrided method to not be called on method's __CLASS__", function() {

                    $bar = new SubBar();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Bar')->not->toReceive('overrided');
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\SubBar')->toReceive('overrided');
                    $bar->overrided();

                });

            });

            context("when using subbing", function() {

                it("expects called method to be called and stubbed as expected", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->andReturn('Hello Boy!', 'Hello Man!');
                    expect($foo->message())->toBe('Hello Boy!');
                    expect($foo->message())->toBe('Hello Man!');

                });

                it("expects called method to be called and stubbed as expected", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->andRun(function() {
                        return 'Hello Girl!';
                    });
                    expect($foo->message())->toBe('Hello Girl!');

                });

            });

            context("with chain of methods", function() {

                it("expects called chain to be called", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('a->b->c')->andReturn('something');
                    $query = $foo->a();
                    $select = $query->b();
                    expect($select->c())->toBe('something');

                });

                it("expects not called chain to be uncalled", function() {

                    $foo = new Foo();
                    expect($foo)->not->toReceive('a->c->b')->andReturn('something');
                    $query = $foo->a();
                    $select = $query->b();
                    $select->c();

                });

                it('auto monkey patch core classes using a stub when possible', function() {

                    expect('PDO')->toReceive('prepare->fetchAll')->andReturn([['name' => 'bob']]);
                    $user = new User();
                    expect($user->all())->toBe([['name' => 'bob']]);

                });

                it('allows to mix static/dynamic methods', function() {

                    Monkey::patch('PDO', Stub::create());
                    expect('Kahlan\Spec\Fixture\Plugin\Monkey\User')->toReceive('::create->all')->andReturn([['name' => 'bob']]);
                    $user = User::create();
                    expect($user->all())->toBe([['name' => 'bob']]);

                });

            });

        });

        context("with static call", function() {

            it("expects called method to be called", function() {

                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::version');
                Foo::version();

            });

            it("expects method called in the past to be uncalled", function() {

                Foo::version();
                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('::version');

            });

            it("expects uncalled method to be uncalled", function() {

                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('::version');

            });

            it("expects called method to be called exactly once", function() {

                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::version')->once();
                Foo::version();

            });

            it("expects called method to be called exactly a specified times", function() {

                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::version')->times(3);
                Foo::version();
                Foo::version();
                Foo::version();

            });

            it("expects called method not called exactly a specified times to be uncalled", function() {

                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('::version')->times(1);
                Foo::version();
                Foo::version();

            });

            it("expects called method to not be dynamically called", function() {

                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('version');
                Foo::version();

            });

            it("expects called method on instance to be called on classname", function() {

                $foo = new Foo();
                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::version');
                $foo::version();

            });

            it("expects called method on instance to not be dynamically called", function() {

                $foo = new Foo();
                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('version');
                $foo::version();

            });

            it("expects called method on instance to be called on classname (alternative syntax)", function() {

                $foo = new Foo();
                expect($foo)->toReceive('::version');
                $foo::version();

            });

            context("with chain of methods", function() {

                it("expects called chain to be called", function() {

                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::getQuery::newQuery::from')->andReturn('something');
                    $query = Foo::getQuery();
                    $select = $query::newQuery();
                    expect($select::from())->toBe('something');

                });

                it("expects not called chain to be uncalled", function() {

                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('::getQuery::from::newQuery')->andReturn('something');
                    $query = Foo::getQuery();
                    $select = $query::newQuery();
                    $select::from();

                });

            });

        });

        context("with ordered enabled", function() {

            describe("::match()", function() {

                it("expects called methods to be called in a defined order", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered;
                    expect($foo)->toReceive('::version')->ordered;
                    expect($foo)->toReceive('bar')->ordered;
                    $foo->message();
                    $foo::version();
                    $foo->bar();

                });

                it("expects called methods to be called in a defined order only once", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered->once();
                    expect($foo)->toReceive('::version')->ordered->once();
                    expect($foo)->toReceive('bar')->ordered->once();
                    $foo->message();
                    $foo::version();
                    $foo->bar();

                });

                it("expects called methods to be called in a defined order a specific number of times", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered->times(1);
                    expect($foo)->toReceive('::version')->ordered->times(2);
                    expect($foo)->toReceive('bar')->ordered->times(3);
                    $foo->message();
                    $foo::version();
                    $foo::version();
                    $foo->bar();
                    $foo->bar();
                    $foo->bar();

                });

                it("expects called methods called in a different order to be uncalled", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered;
                    expect($foo)->not->toReceive('bar')->ordered;
                    $foo->bar();
                    $foo->message();

                });

                it("expects called methods called a specific number of times but in a different order to be uncalled", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered->times(1);
                    expect($foo)->toReceive('::version')->ordered->times(2);
                    expect($foo)->not->toReceive('bar')->ordered->times(1);
                    $foo->message();
                    $foo::version();
                    $foo->bar();
                    $foo::version();

                });

                it("expects to work as `toReceive` for the first call", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message');
                    $foo->message();

                });

                it("expects called methods are consumated", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered;
                    expect($foo)->not->toReceive('message')->ordered;
                    $foo->message();

                });

                it("expects called methods are consumated using classname", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('message')->ordered;
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('message')->ordered;
                    $foo->message();

                });

            });

        });

    });

    describe("->description()", function() {

        it("returns the description message for not received call", function() {

            $stub = Stub::create();
            $matcher = new ToReceive($stub, 'method');

            $matcher->resolve([
                'instance' => $matcher,
                'data'     => [
                    'actual'   => $stub,
                    'expected' => 'method',
                    'logs'     => []
                ]
            ]);

            $actual = $matcher->description();

            expect($actual['description'])->toBe('receive the expected method.');
            expect($actual['data'])->toBe([
                'actual received calls' => [],
                'expected to receive'   => 'method'
            ]);

        });

        it("returns the description message for not received call the specified number of times", function() {

            $stub = Stub::create();
            $matcher = new ToReceive($stub, 'method');
            $matcher->times(2);

            $matcher->resolve([
                'instance' => $matcher,
                'data'     => [
                    'actual'   => $stub,
                    'expected' => 'method',
                    'logs'     => []
                ]
            ]);

            $actual = $matcher->description();

            expect($actual['description'])->toBe('receive the expected method the expected times.');
            expect($actual['data'])->toBe([
                'actual received calls'   => [],
                'expected to receive'     => 'method',
                'expected received times' => 2
            ]);

        });

        it("returns the description message for wrong passed arguments", function() {

            $stub = Stub::create();
            $matcher = new ToReceive($stub, 'method');
            $matcher->with('Hello World!');

            $stub->method('Good Bye!');

            $matcher->resolve([
                'instance' => $matcher,
                'data'     => [
                    'actual'   => $stub,
                    'expected' => 'method',
                    'logs'     => []
                ]
            ]);

            $actual = $matcher->description();

            expect($actual['description'])->toBe('receive the expected method with expected parameters.');
            expect($actual['data'])->toBe([
                'actual received'                 => 'method',
                'actual received times'           => 1,
                'actual received parameters list' => [['Good Bye!']],
                'expected to receive'             => 'method',
                'expected parameters'             => ['Hello World!']
            ]);

        });

    });

    describe("->resolve()", function() {

        it("throw an exception when not explicitly defining the stub value", function() {

            expect(function() {
                $foo = new Foo();
                $matcher = new ToReceive($foo, 'a->c->b');
                $matcher->resolve([
                    'instance' => $matcher,
                    'data'     => [
                        'actual'   => $foo,
                        'expected' => 'a->c->b',
                        'logs'     => []
                    ]
                ]);
            })->toThrow(new Exception("Spying doesn't work with long chain syntax your must explicitly set the return value using `andReturn()`."));

        });

    });

});
