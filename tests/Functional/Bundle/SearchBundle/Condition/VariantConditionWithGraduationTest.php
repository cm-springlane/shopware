<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Tests\Functional\Bundle\SearchBundle\Condition;

use Shopware\Bundle\SearchBundle\Condition\VariantCondition;
use Shopware\Bundle\SearchBundle\Sorting\PriceSorting;
use Shopware\Bundle\StoreFrontBundle\Struct\ListProduct;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContext;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Category\Category;
use Shopware\Tests\Functional\Bundle\StoreFrontBundle\TestCase;

class VariantConditionWithGraduationTest extends TestCase
{
    private $groups = [];

    protected function setUp()
    {
        parent::setUp();
        $this->setConfig('hideNoInStock', false);
    }

    public function testSingleNotExpandOptionSortByPrice()
    {
        $this->groups = $this->helper->insertConfiguratorData(
            [
                'color' => ['red', 'green'],
                'size' => ['xl', 'l'],
            ]
        );

        $condition = $this->createCondition(['xl', 'l'], 'size');
        $sorting = new PriceSorting();
        $sorting->setDirection(PriceSorting::SORT_ASC);

        $result = $this->search(
            [
                'A' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green'], 'size' => ['xl', 'l']]),
                    'graduationPrices' => [
                        [60, 50],
                        [60, 40],
                        [70, 30],
                        [80, 40],
                    ],
                ],
                'B' => ['groups' => $this->buildConfigurator(['color' => ['green'], 'size' => ['xl']]),
                        'graduationPrices' => [
                            [60, 50],
                        ],
                    ],
                'C' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green'], 'size' => ['xl']]),
                        'graduationPrices' => [
                            [100, 50],
                            [110, 20],
                        ],
                    ],
            ],
            ['B1', 'A1', 'C1'],
            null,
            [$condition],
            [],
            [$sorting],
            null,
            ['useLastGraduationForCheapestPrice' => true],
            true
        );

        $this->assertPrices(
            $result->getProducts(),
            [
                'A1' => 30,
                'B1' => 50,
                'C1' => 20,
            ]
        );

        $this->assertSearchResultSorting($result, ['C1', 'A1', 'B1']);
    }

    public function testSingleNotExpandOptionWithLastStockSortByPrice()
    {
        $this->groups = $this->helper->insertConfiguratorData(
            [
                'color' => ['red', 'green'],
                'size' => ['xl', 'l'],
            ]
        );

        $condition = $this->createCondition(['xl', 'l'], 'size');
        $sorting = new PriceSorting();
        $sorting->setDirection(PriceSorting::SORT_ASC);

        $result = $this->search(
            [
                'A' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green'], 'size' => ['xl', 'l']]),
                    'graduationPrices' => [
                        [80, 60],
                        [60, 40],
                        [70, 30],
                        [80, 40],
                    ],
                    'inStock' => [
                        20,
                        0,
                        0,
                        0,
                    ],
                ],
                'B' => ['groups' => $this->buildConfigurator(['color' => ['green'], 'size' => ['xl']]),
                    'graduationPrices' => [
                        [60, 50],
                    ],
                ],
                'C' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green'], 'size' => ['xl']]),
                    'graduationPrices' => [
                        [100, 50],
                        [110, 20],
                    ],
                ],
            ],
            ['B1', 'A1', 'C1'],
            null,
            [$condition],
            [],
            [$sorting],
            null,
            ['useLastGraduationForCheapestPrice' => true],
            true
        );

        $this->assertPrices(
            $result->getProducts(),
            [
                'A1' => 60,
                'B1' => 50,
                'C1' => 20,
            ]
        );

        $this->assertSearchResultSorting($result, ['C1', 'B1', 'A1']);
    }

    public function testMultiNotExpandOptionSortByPrice()
    {
        $this->groups = $this->helper->insertConfiguratorData(
            [
                'color' => ['red', 'green', 'blue'],
                'size' => ['s', 'm', 'l', 'xl'],
            ]
        );

        $conditionColor = $this->createCondition(['red', 'green'], 'color');
        $conditionSize = $this->createCondition(['l', 'xl'], 'size');
        $sorting = new PriceSorting();
        $sorting->setDirection(PriceSorting::SORT_ASC);

        $result = $this->search(
            [
                'A' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green'], 'size' => ['l', 'xl']]),
                    'graduationPrices' => [
                        [60, 50],
                        [60, 40],
                        [70, 30],
                        [80, 40],
                    ],
                ],
                'B' => ['groups' => $this->buildConfigurator(['color' => ['green'], 'size' => ['s', 'xl']]),
                    'graduationPrices' => [
                        [60, 50],
                        [60, 40],
                    ],
                ],
                'C' => ['groups' => $this->buildConfigurator(['color' => ['blue', 'green']]),
                    'graduationPrices' => [
                        [60, 50],
                        [60, 10],
                    ],
                ],
                'D' => ['groups' => $this->buildConfigurator(['color' => ['blue', 'green'], 'size' => ['s', 'l', 'xl']]),
                    'graduationPrices' => [
                        [60, 50],
                        [60, 20],
                        [70, 30],
                        [80, 40],
                    ],
                ],
            ],
            ['A1', 'B2', 'D2'],
            null,
            [$conditionColor, $conditionSize],
            [],
            [$sorting],
            null,
            ['useLastGraduationForCheapestPrice' => true],
            true
        );

        $this->assertPrices(
            $result->getProducts(),
            [
                'A1' => 30,
                'B2' => 40,
                'D2' => 20,
            ]
        );

        $this->assertSearchResultSorting($result, ['D2', 'A1', 'B2']);
    }

    public function testSingleExpandOptionSortByPrice()
    {
        $this->groups = $this->helper->insertConfiguratorData(
            [
                'color' => ['red', 'green'],
                'size' => ['xl', 'l'],
            ]
        );

        $condition = $this->createCondition(['xl', 'l'], 'size', true);
        $sorting = new PriceSorting();
        $sorting->setDirection(PriceSorting::SORT_ASC);

        $result = $this->search(
            [
                'A' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green'], 'size' => ['xl', 'l']]),
                    'graduationPrices' => [
                        [60, 50],
                        [60, 20],
                        [70, 30],
                        [80, 40],
                    ],
                ],
                'B' => ['groups' => $this->buildConfigurator(['color' => ['green'], 'size' => ['xl']]),
                    'graduationPrices' => [
                        [60, 55],
                        [60, 20],
                    ],
                ],
                'C' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green']]),
                    'graduationPrices' => [
                        [60, 50],
                        [60, 20],
                    ],
                ],
            ],
            ['A1', 'A2', 'B1'],
            null,
            [$condition],
            [],
            [$sorting],
            null,
            ['useLastGraduationForCheapestPrice' => true],
            true
        );

        $this->assertPrices(
            $result->getProducts(),
            [
                'A1' => 30,
                'A2' => 20,
                'B1' => 55,
            ]
        );

        $this->assertSearchResultSorting($result, ['A2', 'A1', 'B1']);
    }

    public function testSingleExpandOptionWithLastStockSortByPrice()
    {
        $this->groups = $this->helper->insertConfiguratorData(
            [
                'color' => ['red', 'green'],
                'size' => ['xl', 'l'],
            ]
        );

        $condition = $this->createCondition(['xl', 'l'], 'size', true);
        $sorting = new PriceSorting();
        $sorting->setDirection(PriceSorting::SORT_ASC);

        $result = $this->search(
            [
                'A' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green'], 'size' => ['xl', 'l']]),
                    'graduationPrices' => [
                        [60, 50],
                        [60, 20],
                        [70, 30],
                        [80, 40],
                    ],
                    'inStock' => [
                        0,
                        0,
                        0,
                        0,
                    ],
                ],
                'B' => ['groups' => $this->buildConfigurator(['color' => ['green'], 'size' => ['xl']]),
                    'graduationPrices' => [
                        [60, 55],
                    ],
                ],
                'C' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green']]),
                    'graduationPrices' => [
                        [60, 50],
                        [60, 20],
                    ],
                ],
                'D' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green'], 'size' => ['xl', 'l']]),
                    'graduationPrices' => [
                        [60, 50],
                        [60, 20],
                        [70, 30],
                        [80, 40],
                    ],
                    'inStock' => [
                        20,
                        0,
                        0,
                        20,
                    ],
                ],
                'E' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green'], 'size' => ['xl', 'l']]),
                    'graduationPrices' => [
                        [90, 50],
                        [90, 20],
                        [70, 30],
                        [80, 40],
                    ],
                    'inStock' => [
                        0,
                        20,
                        0,
                        20,
                    ],
                ],
            ],
            ['A1', 'A2', 'B1', 'D1', 'D2', 'E1', 'E2'],
            null,
            [$condition],
            [],
            [$sorting],
            null,
            ['useLastGraduationForCheapestPrice' => true],
            true
        );

        $this->assertPrices(
            $result->getProducts(),
            [
                'A1' => 60,
                'A2' => 60,
                'B1' => 55,
                'D1' => 50,
                'D2' => 40,
                'E1' => 90,
                'E2' => 20,
            ]
        );

        $this->assertSearchResultSorting($result, ['E2', 'D2', 'D1', 'B1', 'A1', 'A2', 'E1']);
    }

    public function testMultiExpandOptionSortByPrice()
    {
        $this->groups = $this->helper->insertConfiguratorData(
            [
                'color' => ['red', 'blue', 'green'],
                'size' => ['s', 'm', 'l', 'xl'],
            ]
        );

        $conditionColor = $this->createCondition(['red', 'green'], 'color', true);
        $conditionSize = $this->createCondition(['l', 'xl'], 'size', true);
        $sorting = new PriceSorting();
        $sorting->setDirection(PriceSorting::SORT_ASC);

        $result = $this->search(
            [
                'A' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green'], 'size' => ['l', 'xl']]),
                    'graduationPrices' => [
                        [60, 50],
                        [60, 20],
                        [100, 30],
                        [80, 40],
                    ],
                ],
                'B' => ['groups' => $this->buildConfigurator(['color' => ['green'], 'size' => ['s', 'xl']]),
                    'graduationPrices' => [
                        [120, 40],
                        [88, 23],
                    ],
                ],
                'C' => ['groups' => $this->buildConfigurator(['color' => ['blue', 'green']]),
                    'graduationPrices' => [
                        [55, 45],
                        [55, 15],
                    ],
                ],
                'D' => ['groups' => $this->buildConfigurator(['color' => ['blue', 'green'], 'size' => ['s', 'l', 'xl']]),
                    'graduationPrices' => [
                        [88, 22],
                        [66, 11],
                        [25, 19],
                        [99, 66],
                        [69, 18],
                        [77, 66],
                        [55, 44],
                        [100, 50],
                    ],
                ],
            ],
            [
                'A1', 'A2', 'A3', 'A4',
                'B2',
                'D5', 'D6',
            ],
            null,
            [$conditionColor, $conditionSize],
            [],
            [$sorting],
            null,
            ['useLastGraduationForCheapestPrice' => true],
            true
        );

        $this->assertPrices(
            $result->getProducts(),
            [
                'A1' => 50,
                'A2' => 20,
                'A3' => 30,
                'A4' => 40,
                'B2' => 23,
                'D5' => 18,
                'D6' => 66,
            ]
        );

        $this->assertSearchResultSorting($result, ['D5', 'A2', 'B2', 'A3', 'A4', 'A1', 'D6']);
    }

    public function testMultiCrossExpandOptionSortByPrice()
    {
        $this->groups = $this->helper->insertConfiguratorData(
            [
                'color' => ['red', 'blue', 'green'],
                'size' => ['s', 'm', 'l', 'xl'],
            ]
        );

        $conditionColor = $this->createCondition(['red', 'blue'], 'color', true);
        $conditionSize = $this->createCondition(['l', 'xl'], 'size');
        $sorting = new PriceSorting();
        $sorting->setDirection(PriceSorting::SORT_ASC);

        $result = $this->search(
            [
                'A' => ['groups' => $this->buildConfigurator(['color' => ['red', 'green'], 'size' => ['l', 'xl']]),
                    'graduationPrices' => [
                        [88, 22],
                        [66, 20],
                        [25, 11],
                        [99, 10],
                    ],
                ],
                'B' => ['groups' => $this->buildConfigurator(['color' => ['green'], 'size' => ['s', 'xl']]),
                    'graduationPrices' => [
                        [88, 22],
                        [66, 11],
                    ],
                ],
                'C' => ['groups' => $this->buildConfigurator(['color' => ['blue', 'green']]),
                    'graduationPrices' => [
                        [88, 22],
                        [66, 11],
                    ],
                ],
                'D' => ['groups' => $this->buildConfigurator(['color' => ['blue', 'green'], 'size' => ['s', 'l', 'xl']]),
                    'graduationPrices' => [
                        [88, 22],
                        [66, 11],
                        [25, 19],
                        [99, 66],
                        [69, 18],
                        [77, 5],
                        [55, 44],
                        [100, 50],
                    ],
                ],
            ],
            ['A1', 'D2'],
            null,
            [$conditionColor, $conditionSize],
            [],
            [$sorting],
            null,
            ['useLastGraduationForCheapestPrice' => true],
            true
        );

        $this->assertPrices(
            $result->getProducts(),
            [
                'A1' => 20,
                'D2' => 11,
            ]
        );

        $this->assertSearchResultSorting($result, ['D2', 'A1']);
    }

    /**
     * Creates and return the VariantCondition of the given options of the given group.
     *
     * @param array  $options
     * @param string $groupName
     * @param bool   $expand
     *
     * @return VariantCondition
     */
    public function createCondition($options, $groupName, $expand = false)
    {
        $mapping = $this->mapOptions();

        $ids = array_intersect_key(
            $mapping['options'],
            array_flip($options)
        );

        return new VariantCondition(array_values($ids), $expand, $mapping['groups'][$groupName]);
    }

    /**
     * Get products and set the graduated prices and inStock of the variants.
     *
     * @param string      $number
     * @param Category    $category
     * @param ShopContext $context
     * @param array       $data
     *
     * @return array
     */
    protected function getProduct(
        $number,
        ShopContext $context,
        Category $category = null,
        $data = []
    ) {
        $product = parent::getProduct($number, $context, $category);

        $configurator = $this->helper->createConfiguratorSet($data['groups']);

        $variants = array_merge([
            'prices' => $this->helper->getGraduatedPrices($context->getCurrentCustomerGroup()->getKey()),
        ], $this->helper->getUnitData());

        $variants = $this->helper->generateVariants(
            $configurator['groups'],
            $number,
            $variants
        );

        if (isset($data['graduationPrices'])) {
            $variantCount = 0;
            foreach ($variants as &$variant) {
                if (isset($data['inStock'][$variantCount])) {
                    $variant['inStock'] = $data['inStock'][$variantCount];
                }

                if (isset($data['graduationPrices'][$variantCount])) {
                    $variant['prices'] = [];
                    $priceCount = 0;
                    foreach ($data['graduationPrices'][$variantCount] as $graduationPrice) {
                        ++$priceCount;
                        $variant['prices'][] = [
                            'from' => $priceCount,
                            'to' => $priceCount + 9,
                            'price' => $graduationPrice,
                            'customerGroupKey' => $context->getCurrentCustomerGroup()->getKey(),
                            'pseudoPrice' => $graduationPrice + 110,
                        ];
                        $priceCount += 9;
                    }
                    $variant['prices'][count($variant['prices']) - 1]['to'] = 'beliebig';
                }

                ++$variantCount;
            }
        }

        if (isset($variants[0]) && isset($variants[0]['prices'])) {
            $product['mainDetail']['prices'] = $variants[0]['prices'];
        }

        $product['configuratorSet'] = $configurator;
        $product['variants'] = $variants;

        return $product;
    }

    /**
     * Assert the cheapest and pseudo prices of the products / variants.
     *
     * @param ListProduct[] $products
     * @param array         $prices
     */
    private function assertPrices(array  $products, array $prices)
    {
        foreach ($products as $product) {
            $number = $product->getNumber();
            if (!isset($prices[$number])) {
                continue;
            }

            $this->assertEquals($prices[$number], $product->getCheapestPrice()->getCalculatedPrice());
        }
    }

    /**
     * Returns the mapping of group and option names to ids.
     *
     * @return array
     */
    private function mapOptions()
    {
        $mapping = [];
        foreach ($this->groups as $group) {
            $mapping['groups'][$group->getName()] = $group->getId();
            foreach ($group->getOptions() as $option) {
                $mapping['options'][$option->getName()] = $option->getId();
            }
        }

        return $mapping;
    }

    /**
     * Creates the structure of the configurator.
     *
     * @param array $expected
     *
     * @return Group[]
     */
    private function buildConfigurator($expected)
    {
        $groups = [];
        foreach ($expected as $group => $optionNames) {
            /* @var $allGroups Group[] */
            foreach ($this->groups as $globalGroup) {
                if ($globalGroup->getName() !== $group) {
                    continue;
                }

                $options = [];
                foreach ($globalGroup->getOptions() as $option) {
                    if (in_array($option->getName(), $optionNames, true)) {
                        $options[] = $option;
                    }
                }

                $clone = clone $globalGroup;
                $clone->setOptions($options);

                $groups[] = $clone;
            }
        }

        return $groups;
    }

    /**
     * Sets the config value and refresh the shop.
     *
     * @param $name
     * @param $value
     */
    private function setConfig($name, $value)
    {
        Shopware()->Container()->get('config_writer')->save($name, $value);
        Shopware()->Container()->get('cache')->clean();
        Shopware()->Container()->get('config')->setShop(Shopware()->Shop());
    }
}
