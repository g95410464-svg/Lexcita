<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SliSiteContent
{
    public static function defaults(): array
    {
        return [
            'home' => [
                'eyebrow' => 'SERVICIOS LEGALES INTEGRADOS',
                'title' => 'Tu gestión legal, más cerca de ti.',
                'description' => 'Un espacio para agendar tus consultas, conectar con tu abogado y dar seguimiento a tus citas.',
                'image' => null,
                'logo' => null,
            ],
            'about' => [
                'eyebrow' => 'CONÓCENOS / SERVICIOS LEGALES INTEGRADOS',
                'title' => 'Tu confianza merece un espacio cercano.',
                'summary' => 'Servicios Legales Integrados S.A. de C.V. · Tu Conexión Legal · Abogados y Notarios · El Salvador.',
                'heading' => 'La atención empieza por conocernos.',
                'paragraph_one' => 'Este portal te permite conocer a los abogados del despacho, consultar su especialidad y organizar una cita según su disponibilidad.',
                'paragraph_two' => 'Antes de agendar, explora nuestro equipo. Si necesitas orientación sobre tu consulta, puedes contactar directamente al despacho.',
            ],
            'theme' => [
                'navy' => '#1d2a44',
                'gold' => '#977045',
                'canvas' => '#f3efe7',
                'sage' => '#e2e8e4',
            ],
        ];
    }

    public static function get(): array
    {
        if (config('organization.id') !== 'servicios-legales-integrados'
            || !DB::getSchemaBuilder()->hasTable('sli_site_settings')) {
            return self::defaults();
        }

        $values = DB::table('sli_site_settings')->pluck('value', 'key');
        $content = self::defaults();
        foreach ($content as $section => $defaults) {
            if (!isset($values[$section])) {
                continue;
            }
            $saved = is_array($values[$section]) ? $values[$section] : json_decode($values[$section], true);
            if (is_array($saved)) {
                $content[$section] = array_replace($defaults, $saved);
            }
        }

        return $content;
    }
}
