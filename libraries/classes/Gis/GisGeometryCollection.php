<?php
/**
 * Handles actions related to GIS GEOMETRYCOLLECTION objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function array_merge;
use function count;
use function mb_strpos;
use function mb_substr;
use function str_split;

/**
 * Handles actions related to GIS GEOMETRYCOLLECTION objects
 */
class GisGeometryCollection extends GisGeometry
{
    /** @var self */
    private static $instance;

    /**
     * A private constructor; prevents direct creation of object.
     */
    private function __construct()
    {
    }

    /**
     * Returns the singleton.
     *
     * @return GisGeometryCollection the singleton
     */
    public static function singleton(): GisGeometryCollection
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisGeometryCollection();
        }

        return self::$instance;
    }

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     */
    public function scaleRow(string $spatial): ScaleData|null
    {
        $min_max = null;

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = mb_substr($spatial, 19, -1);

        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->explodeGeomCol($goem_col);

        foreach ($sub_parts as $sub_part) {
            $type_pos = mb_strpos($sub_part, '(');
            if ($type_pos === false) {
                continue;
            }

            $type = mb_substr($sub_part, 0, $type_pos);

            $gis_obj = GisFactory::factory($type);
            if (! $gis_obj) {
                continue;
            }

            $scale_data = $gis_obj->scaleRow($sub_part);

            $min_max = $min_max === null ? $scale_data : $scale_data?->merge($min_max);
        }

        return $min_max;
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS POLYGON object
     * @param string $label      Label for the GIS POLYGON object
     * @param int[]  $color      Color for the GIS POLYGON object
     * @param array  $scale_data Array containing data related to scaling
     */
    public function prepareRowAsPng(
        $spatial,
        string $label,
        array $color,
        array $scale_data,
        ImageWrapper $image
    ): ImageWrapper {
        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = mb_substr($spatial, 19, -1);
        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->explodeGeomCol($goem_col);

        foreach ($sub_parts as $sub_part) {
            $type_pos = mb_strpos($sub_part, '(');
            if ($type_pos === false) {
                continue;
            }

            $type = mb_substr($sub_part, 0, $type_pos);

            $gis_obj = GisFactory::factory($type);
            if (! $gis_obj) {
                continue;
            }

            $image = $gis_obj->prepareRowAsPng($sub_part, $label, $color, $scale_data, $image);
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS GEOMETRYCOLLECTION object
     * @param string $label      label for the GIS GEOMETRYCOLLECTION object
     * @param int[]  $color      color for the GIS GEOMETRYCOLLECTION object
     * @param array  $scale_data array containing data related to scaling
     * @param TCPDF  $pdf
     *
     * @return TCPDF the modified TCPDF instance
     */
    public function prepareRowAsPdf($spatial, string $label, array $color, array $scale_data, $pdf): TCPDF
    {
        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = mb_substr($spatial, 19, -1);
        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->explodeGeomCol($goem_col);

        foreach ($sub_parts as $sub_part) {
            $type_pos = mb_strpos($sub_part, '(');
            if ($type_pos === false) {
                continue;
            }

            $type = mb_substr($sub_part, 0, $type_pos);

            $gis_obj = GisFactory::factory($type);
            if (! $gis_obj) {
                continue;
            }

            $pdf = $gis_obj->prepareRowAsPdf($sub_part, $label, $color, $scale_data, $pdf);
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS GEOMETRYCOLLECTION object
     * @param string $label      label for the GIS GEOMETRYCOLLECTION object
     * @param int[]  $color      color for the GIS GEOMETRYCOLLECTION object
     * @param array  $scale_data array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, string $label, array $color, array $scale_data): string
    {
        $row = '';

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = mb_substr($spatial, 19, -1);
        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->explodeGeomCol($goem_col);

        foreach ($sub_parts as $sub_part) {
            $type_pos = mb_strpos($sub_part, '(');
            if ($type_pos === false) {
                continue;
            }

            $type = mb_substr($sub_part, 0, $type_pos);

            $gis_obj = GisFactory::factory($type);
            if (! $gis_obj) {
                continue;
            }

            $row .= $gis_obj->prepareRowAsSvg($sub_part, $label, $color, $scale_data);
        }

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial    GIS GEOMETRYCOLLECTION object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS GEOMETRYCOLLECTION object
     * @param int[]  $color      color for the GIS GEOMETRYCOLLECTION object
     * @param array  $scale_data array containing data related to scaling
     *
     * @return string JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl($spatial, int $srid, string $label, array $color, array $scale_data): string
    {
        $row = '';

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = mb_substr($spatial, 19, -1);
        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->explodeGeomCol($goem_col);

        foreach ($sub_parts as $sub_part) {
            $type_pos = mb_strpos($sub_part, '(');
            if ($type_pos === false) {
                continue;
            }

            $type = mb_substr($sub_part, 0, $type_pos);

            $gis_obj = GisFactory::factory($type);
            if (! $gis_obj) {
                continue;
            }

            $row .= $gis_obj->prepareRowAsOl($sub_part, $srid, $label, $color, $scale_data);
        }

        return $row;
    }

    /**
     * Splits the GEOMETRYCOLLECTION object and get its constituents.
     *
     * @param string $geom_col geometry collection string
     *
     * @return string[] the constituents of the geometry collection object
     */
    private function explodeGeomCol($geom_col): array
    {
        $sub_parts = [];
        $br_count = 0;
        $start = 0;
        $count = 0;
        foreach (str_split($geom_col) as $char) {
            if ($char === '(') {
                $br_count++;
            } elseif ($char === ')') {
                $br_count--;
                if ($br_count == 0) {
                    $sub_parts[] = mb_substr($geom_col, $start, $count + 1 - $start);
                    $start = $count + 2;
                }
            }

            $count++;
        }

        return $sub_parts;
    }

    /**
     * Generates the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array       $gis_data GIS data
     * @param int         $index    index into the parameter object
     * @param string|null $empty    value for empty points
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gis_data, $index, $empty = ''): string
    {
        $geom_count = $gis_data['GEOMETRYCOLLECTION']['geom_count'] ?? 1;
        $wkt = 'GEOMETRYCOLLECTION(';
        for ($i = 0; $i < $geom_count; $i++) {
            if (! isset($gis_data[$i]['gis_type'])) {
                continue;
            }

            $type = $gis_data[$i]['gis_type'];
            $gis_obj = GisFactory::factory($type);
            if (! $gis_obj) {
                continue;
            }

            $wkt .= $gis_obj->generateWkt($gis_data, $i, $empty) . ',';
        }

        if (isset($gis_data[0]['gis_type'])) {
            $wkt = mb_substr($wkt, 0, -1);
        }

        return $wkt . ')';
    }

    /**
     * Generates parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value of the GIS column
     *
     * @return array parameters for the GIS editor from the value of the GIS column
     */
    public function generateParams($value): array
    {
        $params = [];
        $data = GisGeometry::generateParams($value);
        $params['srid'] = $data['srid'];
        $wkt = $data['wkt'];

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = mb_substr($wkt, 19, -1);
        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->explodeGeomCol($goem_col);
        $params['GEOMETRYCOLLECTION']['geom_count'] = count($sub_parts);

        $i = 0;
        foreach ($sub_parts as $sub_part) {
            $type_pos = mb_strpos($sub_part, '(');
            if ($type_pos === false) {
                continue;
            }

            $type = mb_substr($sub_part, 0, $type_pos);
            /**
             * @var GisMultiPolygon|GisPolygon|GisMultiPoint|GisPoint|GisMultiLineString|GisLineString $gis_obj
             */
            $gis_obj = GisFactory::factory($type);
            if (! $gis_obj) {
                continue;
            }

            $params = array_merge($params, $gis_obj->generateParams($sub_part, $i));
            $i++;
        }

        return $params;
    }
}
