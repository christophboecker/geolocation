<?php

/**
 * Eine Toolklasse für Metafelder auf Basis des Picker-Widgets.
 */

namespace FriendsOfRedaxo\Geolocation\Picker;

use FriendsOfRedaxo\Geolocation\Calc\Point;
use rex_extension_point;
use rex_fragment;
use rex_sql;

use function call_user_func;
use function count;
use function is_callable;

class PickerMetafield
{
    /** @api */
    public const META_FIELD_TYPE = 'LocationPicker (Geolocation)';

    /**
     * Baut im EP METAINFO_CUSTOM_FIELD den Formularcode für einen Location-Picker ein.
     *
     * @api
     * @param rex_extension_point<array<mixed>> $ep
     */
    public static function createMetaField(rex_extension_point $ep): void
    {
        $subject = $ep->getSubject();
        if (self::META_FIELD_TYPE !== $subject['type']) {
            return;
        }

        /**
         * Die aktuelle Koordinate abrufen; es muss ein Array aus
         * zwei Werten sein.
         * In den anderen Fällen wird ein Fallback-Array aus zwei Leerstrings erzeugt.
         * @var array<string> $value
         */
        $value = $subject['values'];
        if (2 !== count($value)) {
            $value = ['', ''];
        }

        /**
         * Das Picker-Fragment initialisieren;
         * Zwei auf dem Feldnamen basierende Eingabeelemente zur Verfügung stellen
         * Den aktuellen Wert bzw. null zuweisen.
         */
        $baseName = str_replace('rex-metainfo-', '', $subject[3]);
        $picker = PickerWidget::factoryInternal($baseName . '[lat]', $baseName . '[lng]')
            ->setValue($value[0], $value[1]);

        /**
         * Wenn Params gefüllt ist, muss es ein Callback sein, der das Picker-Widget formatiert.
         */
        /** @var rex_sql $sql */
        $sql = $subject['sql'];
        $callback = trim($sql->getValue('params'));
        if ('' < $callback && is_callable($callback)) {
            call_user_func($callback, $picker);
        }

        /**
         * den Formulareintrag (DL / DT / DD) erzeugen.
         */
        $elements = [
            [
                'label' => $subject['4'],
                'field' => $picker->parse(),
            ],
        ];
        $fragment = new rex_fragment();
        $fragment->setVar('elements', $elements, false);
        $subject[0] = $fragment->parse('core/form/form.php');

        $ep->setSubject($subject);
    }
}
