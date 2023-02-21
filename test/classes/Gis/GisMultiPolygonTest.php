<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisMultiPolygon;
use PhpMyAdmin\Gis\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

/**
 * @covers \PhpMyAdmin\Gis\GisMultiPolygon
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GisMultiPolygonTest extends GisGeomTestCase
{
    /** @var    GisMultiPolygon */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = GisMultiPolygon::singleton();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Provide some common data to data providers
     *
     * @return array common data for data providers
     */
    private static function getData(): array
    {
        return [
            'MULTIPOLYGON' => [
                'no_of_polygons' => 2,
                0 => [
                    'no_of_lines' => 2,
                    0 => [
                        'no_of_points' => 5,
                        0 => [
                            'x' => 35,
                            'y' => 10,
                        ],
                        1 => [
                            'x' => 10,
                            'y' => 20,
                        ],
                        2 => [
                            'x' => 15,
                            'y' => 40,
                        ],
                        3 => [
                            'x' => 45,
                            'y' => 45,
                        ],
                        4 => [
                            'x' => 35,
                            'y' => 10,
                        ],
                    ],
                    1 => [
                        'no_of_points' => 4,
                        0 => [
                            'x' => 20,
                            'y' => 30,
                        ],
                        1 => [
                            'x' => 35,
                            'y' => 32,
                        ],
                        2 => [
                            'x' => 30,
                            'y' => 20,
                        ],
                        3 => [
                            'x' => 20,
                            'y' => 30,
                        ],
                    ],
                ],
                1 => [
                    'no_of_lines' => 1,
                    0 => [
                        'no_of_points' => 4,
                        0 => [
                            'x' => 123,
                            'y' => 0,
                        ],
                        1 => [
                            'x' => 23,
                            'y' => 30,
                        ],
                        2 => [
                            'x' => 17,
                            'y' => 63,
                        ],
                        3 => [
                            'x' => 123,
                            'y' => 0,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return array data for testGenerateWkt
     */
    public static function providerForTestGenerateWkt(): array
    {
        $temp = [
            0 => self::getData(),
        ];

        $temp1 = $temp;
        $temp1[0]['MULTIPOLYGON']['no_of_polygons'] = 0;

        $temp2 = $temp;
        $temp2[0]['MULTIPOLYGON'][1]['no_of_lines'] = 0;

        $temp3 = $temp;
        $temp3[0]['MULTIPOLYGON'][1][0]['no_of_points'] = 3;

        return [
            [
                $temp,
                0,
                null,
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',
            ],
            // at lease one polygon should be there
            [
                $temp1,
                0,
                null,
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
            ],
            // a polygon should have at least one ring
            [
                $temp2,
                0,
                null,
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',
            ],
            // a ring should have at least four points
            [
                $temp3,
                0,
                '0',
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',
            ],
        ];
    }

    /**
     * data provider for testGenerateParams
     *
     * @return array data for testGenerateParams
     */
    public static function providerForTestGenerateParams(): array
    {
        $temp = self::getData();

        $temp1 = self::getData();
        $temp1['gis_type'] = 'MULTIPOLYGON';

        return [
            [
                "'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10),"
                . "(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',124",
                null,
                [
                    'srid' => 124,
                    0 => $temp,
                ],
            ],
            [
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',
                2,
                [2 => $temp1],
            ],
        ];
    }

    /**
     * test getShape method
     *
     * @param array  $row_data array of GIS data
     * @param string $shape    expected shape in WKT
     *
     * @dataProvider providerForTestGetShape
     */
    public function testGetShape(array $row_data, string $shape): void
    {
        $this->assertEquals($shape, $this->object->getShape($row_data));
    }

    /**
     * data provider for testGetShape
     *
     * @return array data for testGetShape
     */
    public static function providerForTestGetShape(): array
    {
        return [
            [
                [
                    'parts' => [
                        0 => [
                            'points' => [
                                0 => [
                                    'x' => 10,
                                    'y' => 10,
                                ],
                                1 => [
                                    'x' => 10,
                                    'y' => 40,
                                ],
                                2 => [
                                    'x' => 50,
                                    'y' => 40,
                                ],
                                3 => [
                                    'x' => 50,
                                    'y' => 10,
                                ],
                                4 => [
                                    'x' => 10,
                                    'y' => 10,
                                ],
                            ],
                        ],
                        1 => [
                            'points' => [
                                0 => [
                                    'x' => 60,
                                    'y' => 40,
                                ],
                                1 => [
                                    'x' => 75,
                                    'y' => 65,
                                ],
                                2 => [
                                    'x' => 90,
                                    'y' => 40,
                                ],
                                3 => [
                                    'x' => 60,
                                    'y' => 40,
                                ],
                            ],
                        ],
                        2 => [
                            'points' => [
                                0 => [
                                    'x' => 20,
                                    'y' => 20,
                                ],
                                1 => [
                                    'x' => 40,
                                    'y' => 20,
                                ],
                                2 => [
                                    'x' => 25,
                                    'y' => 30,
                                ],
                                3 => [
                                    'x' => 20,
                                    'y' => 20,
                                ],
                            ],
                        ],
                    ],
                ],
                'MULTIPOLYGON(((10 10,10 40,50 40,50 10,10 10),(20 20,40 20,25 30'
                    . ',20 20)),((60 40,75 65,90 40,60 40)))',
            ],
        ];
    }

    /**
     * data provider for testScaleRow
     *
     * @return array data for testScaleRow
     */
    public static function providerForTestScaleRow(): array
    {
        return [
            [
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),((105 0,56 20,78 73,105 0)))',
                new ScaleData(147, 16, 83, 0),
            ],
            [
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20'
                    . ',20 30)),((105 0,56 20,78 73,105 0)))',
                new ScaleData(105, 10, 73, 0),
            ],
        ];
    }

    /**
     * @requires extension gd
     */
    public function testPrepareRowAsPng(): void
    {
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        $this->assertNotNull($image);
        $return = $this->object->prepareRowAsPng(
            'MULTIPOLYGON(((5 5,95 5,95 95,5 95,5 5),(10 10,10 40,40 40,40 10,10 10),(60 60,90 60,90 90,60 90,6'
            . '0 60)),((-5 -5,-95 -5,-95 -95,-5 -95,-5 -5),(-10 -10,-10 -40,-40 -40,-40 -10,-10 -10),(-60 -60,-90'
            . ' -60,-90 -90,-60 -90,-60 -60)))',
            'image',
            [176, 46, 224],
            ['x' => -202, 'y' => -125, 'scale' => 0.50, 'height' => 124],
            $image
        );
        $this->assertEquals(200, $return->width());
        $this->assertEquals(124, $return->height());

        $fileExpected = $this->testDir . '/multipolygon-expected.png';
        $fileActual = $this->testDir . '/multipolygon-actual.png';
        $this->assertTrue($image->png($fileActual));
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param string $label      label for the GIS MULTIPOLYGON object
     * @param int[]  $color      color for the GIS MULTIPOLYGON object
     * @param array  $scale_data array containing data related to scaling
     *
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        array $scale_data,
        TCPDF $pdf
    ): void {
        $return = $this->object->prepareRowAsPdf($spatial, $label, $color, $scale_data, $pdf);

        $fileExpected = $this->testDir . '/multipolygon-expected.pdf';
        $fileActual = $this->testDir . '/multipolygon-actual.pdf';
        $return->Output($fileActual, 'F');
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * data provider for testPrepareRowAsPdf() test case
     *
     * @return array test data for testPrepareRowAsPdf() test case
     */
    public static function providerForPrepareRowAsPdf(): array
    {
        return [
            [
                'MULTIPOLYGON(((5 5,95 5,95 95,5 95,5 5),(10 10,10 40,40 40,40 10,10 10),(60 60,90 60,90 90,60 90,6'
                . '0 60)),((-5 -5,-95 -5,-95 -95,-5 -95,-5 -5),(-10 -10,-10 -40,-40 -40,-40 -10,-10 -10),(-60 -60,-90'
                . ' -60,-90 -90,-60 -90,-60 -60)))',
                'pdf',
                [176, 46, 224],
                ['x' => -110, 'y' => -157, 'scale' => 0.95, 'height' => 297],
                parent::createEmptyPdf('MULTIPOLYGON'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string $spatial   GIS MULTIPOLYGON object
     * @param string $label     label for the GIS MULTIPOLYGON object
     * @param int[]  $color     color for the GIS MULTIPOLYGON object
     * @param array  $scaleData array containing data related to scaling
     * @param string $output    expected output
     *
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        string $spatial,
        string $label,
        array $color,
        array $scaleData,
        string $output
    ): void {
        $svg = $this->object->prepareRowAsSvg($spatial, $label, $color, $scaleData);
        $this->assertEquals($output, $svg);
    }

    /**
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array test data for testPrepareRowAsSvg() test case
     */
    public static function providerForPrepareRowAsSvg(): array
    {
        return [
            [
                'MULTIPOLYGON(((5 5,95 5,95 95,5 95,5 5),(10 10,10 40,40 40,40 10,10 10),(60 60,90 60,90 90,60 90,6'
                . '0 60)),((-5 -5,-95 -5,-95 -95,-5 -95,-5 -5),(-10 -10,-10 -40,-40 -40,-40 -10,-10 -10),(-60 -60,-90'
                . ' -60,-90 -90,-60 -90,-60 -60)))',
                'svg',
                [176, 46, 224],
                [
                    'x' => -50,
                    'y' => -50,
                    'scale' => 2,
                    'height' => 400,
                ],
                '<path d=" M 110, 290 L 290, 290 L 290, 110 L 110, 110 Z  M 120, 280 L 120, 220 L 180, 220 L 180, 28'
                . '0 Z  M 220, 180 L 280, 180 L 280, 120 L 220, 120 Z " name="svg" class="multipolygon vector" stroke='
                . '"black" stroke-width="0.5" fill="#b02ee0" fill-rule="evenodd" fill-opacity="0.8" id="svg1234567890"'
                . '/><path d=" M 90, 310 L -90, 310 L -90, 490 L 90, 490 Z  M 80, 320 L 80, 380 L 20, 380 L 20, 320 Z '
                . ' M -20, 420 L -80, 420 L -80, 480 L -20, 480 Z " name="svg" class="multipolygon vector" stroke="bla'
                . 'ck" stroke-width="0.5" fill="#b02ee0" fill-rule="evenodd" fill-opacity="0.8" id="svg1234567890"/>',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS MULTIPOLYGON object
     * @param int[]  $color      color for the GIS MULTIPOLYGON object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $color,
        array $scale_data,
        string $output
    ): void {
        $ol = $this->object->prepareRowAsOl($spatial, $srid, $label, $color, $scale_data);
        $this->assertEquals($output, $ol);
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array test data for testPrepareRowAsOl() test case
     */
    public static function providerForPrepareRowAsOl(): array
    {
        return [
            [
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),((105 0,56 20,78 73,105 0)))',
                4326,
                'Ol',
                [176, 46, 224],
                [
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ],
                'var style = new ol.style.Style({fill: new ol.style.Fill({"color":[176,46,224,0.8]}'
                . '),stroke: new ol.style.Stroke({"color":[0,0,0],"width":0.5}),text: new ol.style.'
                . 'Text({"text":"Ol"})});var minLoc = [0, 0];var maxLoc = [1, 1];var ext = ol.exten'
                . 't.boundingExtent([minLoc, maxLoc]);ext = ol.proj.transformExtent(ext, ol.proj.ge'
                . 't("EPSG:4326"), ol.proj.get(\'EPSG:3857\'));map.getView().fit(ext, map.getSize()'
                . ');var polygonArray = [];var arr = [];var lineArr = [];var line = new ol.geom.Lin'
                . 'earRing(new Array((new ol.geom.Point([136,40]).transform(ol.proj.get("EPSG:4326"'
                . '), ol.proj.get(\'EPSG:3857\'))).getCoordinates(), (new ol.geom.Point([147,83]).t'
                . 'ransform(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinates()'
                . ', (new ol.geom.Point([16,75]).transform(ol.proj.get("EPSG:4326"), ol.proj.get(\''
                . 'EPSG:3857\'))).getCoordinates(), (new ol.geom.Point([136,40]).transform(ol.proj.'
                . 'get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinates()));var coord = li'
                . 'ne.getCoordinates();for (var i = 0; i < coord.length; i++) lineArr.push(coord[i]);arr.'
                . 'push(lineArr);var polygon = new ol.geom.Polygon(arr);polygonArray.push(polygon);var arr = [];v'
                . 'ar lineArr = [];var line = new ol.geom.LinearRing(new Array((new ol.geom.Point(['
                . '105,0]).transform(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoor'
                . 'dinates(), (new ol.geom.Point([56,20]).transform(ol.proj.get("EPSG:4326"), ol.pr'
                . 'oj.get(\'EPSG:3857\'))).getCoordinates(), (new ol.geom.Point([78,73]).transform('
                . 'ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinates(), (new ol'
                . '.geom.Point([105,0]).transform(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857'
                . '\'))).getCoordinates()));var coord = line.getCoordinates();for (var i = 0; i < coord.length;'
                . ' i++) lineArr.push(coord[i]);arr.push(lineArr);var polygon = new ol.geom.Polygon(arr);po'
                . 'lygonArray.push(polygon);var multiPolygon = new ol.geom.MultiPolygon(polygonArra'
                . 'y);var feature = new ol.Feature(multiPolygon);feature.setStyle(style);vectorLaye'
                . 'r.addFeature(feature);',
            ],
        ];
    }
}
