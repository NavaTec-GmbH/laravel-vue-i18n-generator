<?php

use PHPUnit\Framework\TestCase;
use MartinLindhe\VueInternationalizationGenerator\Generator;

class GenerateTest extends TestCase
{
    private function generateLocaleFilesFrom(array $arr)
    {
        $root = sys_get_temp_dir() . '/' . sha1(microtime(true) . mt_rand());
        
        if (!is_dir($root)) {
            mkdir($root, 0777, true);
        }

        foreach ($arr as $key => $val) {

            if (!is_dir($root . '/' . $key)) {
                mkdir($root . '/' . $key);
            }

            foreach ($val as $group => $content) {
                $outFile = $root . '/'. $key . '/' . $group . '.php';
                file_put_contents($outFile, '<?php return ' . var_export($content, true) . ';');
            }
        }

        return $root;
    }

    private function destroyLocaleFilesFrom(array $arr, $root)
    {
        foreach ($arr as $key => $val) {

            foreach ($val as $group => $content) {
                $outFile = $root . '/'. $key . '/' . $group . '.php';
                if (file_exists($outFile)) {
                    unlink($outFile);
                }
            }

            if (is_dir($root . '/' . $key)) {
                rmdir($root . '/' . $key);
            }

        }

        if (is_dir($root)) {
            rmdir($root);
        }
    }

    public function testBasic(): void
    {
        $arr = [
            'en' => [
                'help' => [
                    'yes' => 'yes',
                    'no' => 'no',
                ]
            ],
            'sv' => [
                'help' => [
                    'yes' => 'ja',
                    'no' => 'nej',
                ]
            ]
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JS'
        export default {
            "en": {
                "help": {
                    "yes": "yes",
                    "no": "no"
                }
            },
            "sv": {
                "help": {
                    "yes": "ja",
                    "no": "nej"
                }
            }
        }

        JS;

        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root));
        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testBasicES6Format(): void
    {
        $format = 'es6';

        $arr = [
            'en' => [
                'help' => [
                    'yes' => 'yes',
                    'no' => 'no',
                ]
            ],
            'sv' => [
                'help' => [
                    'yes' => 'ja',
                    'no' => 'nej',
                ]
            ]
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JS'
        export default {
            "en": {
                "help": {
                    "yes": "yes",
                    "no": "no"
                }
            },
            "sv": {
                "help": {
                    "yes": "ja",
                    "no": "nej"
                }
            }
        }

        JS;
        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root, $format));
        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testBasicWithUMDFormat(): void
    {
        $format = 'umd';
        $arr = [
            'en' => [
                'help' => [
                    'yes' => 'yes',
                    'no' => 'no',
                ]
            ],
            'sv' => [
                'help' => [
                    'yes' => 'ja',
                    'no' => 'nej',
                ]
            ]
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JS'
        (function (global, factory) {
            typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
                typeof define === 'function' && define.amd ? define(factory) :
                    typeof global.vuei18nLocales === 'undefined' ? global.vuei18nLocales = factory() : Object.keys(factory()).forEach(function (key) {global.vuei18nLocales[key] = factory()[key]});
        }(this, (function () { 'use strict';
            return {
            "en": {
                "help": {
                    "yes": "yes",
                    "no": "no"
                }
            },
            "sv": {
                "help": {
                    "yes": "ja",
                    "no": "nej"
                }
            }
        }
        
        })));
        JS;
        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root, $format));
        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testBasicWithJSONFormat(): void
    {
        $format = 'json';
        $arr = [
            'en' => [
                'help' => [
                    'yes' => 'yes',
                    'no' => 'no',
                ]
            ],
            'sv' => [
                'help' => [
                    'yes' => 'ja',
                    'no' => 'nej',
                ]
            ]
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JSON'
        {
            "en": {
                "help": {
                    "yes": "yes",
                    "no": "no"
                }
            },
            "sv": {
                "help": {
                    "yes": "ja",
                    "no": "nej"
                }
            }
        }

        JSON;
        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root, $format));
        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testInvalidFormat(): void
    {
        $format = 'es5';
        $arr = [];

        $root = $this->generateLocaleFilesFrom($arr);
        try {
            (new Generator([]))->generateFromPath($root, $format);
        } catch(RuntimeException $e) {
            $this->assertEquals('Invalid format passed: ' . $format, $e->getMessage());

        }
        $this->destroyLocaleFilesFrom($arr, $root);
        $this->assertTrue(true);
    }

    public function testBasicWithTranslationString(): void
    {
        $arr = [
            'en' => [
                'main' => [
                    'hello :name' => 'Hello :name',
                ]
            ],
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JS'
        export default {
            "en": {
                "main": {
                    "hello {name}": "Hello {name}"
                }
            }
        }

        JS;
        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root));
        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testBasicWithEscapedTranslationString(): void
    {
        $arr = [
            'en' => [
                'main' => [
                    'hello :name' => 'Hello :name',
                    'time test 10!:00' => 'Time test 10!:00',
                ]
            ],
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JS'
        export default {
            "en": {
                "main": {
                    "hello {name}": "Hello {name}",
                    "time test 10:00": "Time test 10:00"
                }
            }
        }

        JS;
        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root));
        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testBasicWithVendor(): void
    {
        $arr = [
            'en' => [
                'help' => [
                    'yes' => 'yes',
                    'no' => 'no',
                ]
            ],
            'sv' => [
                'help' => [
                    'yes' => 'ja',
                    'no' => 'nej',
                ]
            ],
            'vendor' => [
                'test-vendor' => [
                    'en' => [
                        'test-lang' => [
                            'maybe' => 'maybe'
                        ]
                    ],
                    'sv' => [
                        'test-lang' => [
                            'maybe' => 'kanske'
                        ]
                    ]
                ]
            ],
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JS'
        export default {
            "en": {
                "help": {
                    "yes": "yes",
                    "no": "no"
                },
                "vendor": {
                    "test-vendor": {
                        "test-lang": {
                            "maybe": "maybe"
                        }
                    }
                }
            },
            "sv": {
                "help": {
                    "yes": "ja",
                    "no": "nej"
                },
                "vendor": {
                    "test-vendor": {
                        "test-lang": {
                            "maybe": "kanske"
                        }
                    }
                }
            }
        }

        JS;
        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root, 'es6', true));

        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testBasicWithVuexLib(): void
    {
        $arr = [
            'en' => [
                'help' => [
                    'yes' => 'yes',
                    'no' => 'no',
                ]
            ],
            'sv' => [
                'help' => [
                    'yes' => 'ja',
                    'no' => 'nej',
                ]
            ]
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JS'
        export default {
            "en": {
                "help": {
                    "yes": "yes",
                    "no": "no"
                }
            },
            "sv": {
                "help": {
                    "yes": "ja",
                    "no": "nej"
                }
            }
        }

        JS;
        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root));

        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testNamed(): void
    {
        $arr = [
            'en' => [
                'help' => [
                    'yes' => 'see :link y :lonk',
                    'no' => [
                        'one' => 'see :link',
                    ]
                ]
            ]
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JS'
        export default {
            "en": {
                "help": {
                    "yes": "see {link} y {lonk}",
                    "no": {
                        "one": "see {link}"
                    }
                }
            }
        }

        JS;
        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root));

        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testNamedWithEscaped(): void
    {
        $arr = [
            'en' => [
                'help' => [
                    'yes' => 'see :link y :lonk at 08!:00',
                    'no' => [
                        'one' => 'see :link',
                    ]
                ]
            ]
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JS'
        export default {
            "en": {
                "help": {
                    "yes": "see {link} y {lonk} at 08:00",
                    "no": {
                        "one": "see {link}"
                    }
                }
            }
        }

        JS;
        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root));

        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testEscapedEscapeCharacter(): void
    {
        $arr = [
            'en' => [
                'help' => [
                    'test escaped' => 'escaped escape char not !!:touched',
                ]
            ]
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JS'
        export default {
            "en": {
                "help": {
                    "test escaped": "escaped escape char not !:touched"
                }
            }
        }

        JS;
        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root));

        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testShouldNotTouchHtmlTags(): void
    {
        $arr = [
            'en' => [
                'help' => [
                    'yes' => 'see <a href="mailto:mail@com">',
                    'no' => 'see <a href=":link">',
                    'maybe' => 'It is a <strong>Test</strong> ok!',
                ]
            ]
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        $expected = <<<'JS'
        export default {
            "en": {
                "help": {
                    "yes": "see <a href=\"mailto:mail@com\">",
                    "no": "see <a href=\"{link}\">",
                    "maybe": "It is a <strong>Test</strong> ok!"
                }
            }
        }

        JS;
        $this->assertEquals($expected, (new Generator([]))->generateFromPath($root));

        $this->destroyLocaleFilesFrom($arr, $root);
    }

    public function testPluralization(): void
    {
        $arr = [
            'en' => [
                'plural' => [
                    'one' => 'There is one apple|There are many apples',
                    'two' => 'There is one apple | There are many apples',
                    'five' => [
                        'three' => 'There is one apple    | There are many apples',
                        'four' => 'There is one apple |     There are many apples',
                    ]
                ]
            ]
        ];

        $root = $this->generateLocaleFilesFrom($arr);
        
        // vue-i18n
        $expected1 = <<<'JS'
        export default {
            "en": {
                "plural": {
                    "one": "There is one apple|There are many apples",
                    "two": "There is one apple | There are many apples",
                    "five": {
                        "three": "There is one apple    | There are many apples",
                        "four": "There is one apple |     There are many apples"
                    }
                }
            }
        }

        JS;
        $this->assertEquals($expected1, (new Generator(['i18nLib' => 'vue-i18n']))->generateFromPath($root));

        // vuex-i18n
        $expected2 = <<<'JS'
        export default {
            "en": {
                "plural": {
                    "one": "There is one apple ::: There are many apples",
                    "two": "There is one apple ::: There are many apples",
                    "five": {
                        "three": "There is one apple ::: There are many apples",
                        "four": "There is one apple ::: There are many apples"
                    }
                }
            }
        }

        JS;
        $this->assertEquals($expected2, (new Generator(['i18nLib' => 'vuex-i18n']))->generateFromPath($root));

        $this->destroyLocaleFilesFrom($arr, $root);
    }
}
