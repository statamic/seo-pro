<?php

return [

    'meta_section' => 'Meta-Daten',
    'meta_section_instruct' => 'Jede URL der Website sollte einen eindeutigen Meta-Titel und eine eindeutige Meta-Beschreibung haben.',

    'title' => 'Meta-Titel',
    'title_instruct' => 'Feld für die Seitentitel auswählen.',

    'description' => 'Meta-Beschreibung',
    'description_instruct' => 'Feld für die Meta-Beschreibungen auswählen.',

    'site_name' => 'Website-Name',
    'site_name_instruct' => 'Name der Website, der für einen einheitlichen Markenauftritt an Meta-Titel angehängt wird.',

    'site_name_position' => 'Position des Namens',
    'site_name_position_instruct' => 'Website-Namen vor oder nach dem Seitentitel anzeigen – oder gar nicht.',

    'site_name_separator' => 'Trennzeichen',
    'site_name_separator_instruct' => 'Trennzeichen zwischen Seitentitel und Website-Namen auswählen.',

    'canonical_url' => 'Kanonische URL',
    'canonical_url_instruct' => 'Feld für kanonische URLs auswählen (üblicherweise `permalink`).',

    'json_ld_section' => 'JSON-LD',

    'json_ld_entity_section' => 'Informationen zur Entität',
    'json_ld_entity_section_instruct' => 'Grundlegende Angaben zur Organisation oder Person, die diese Website vertritt. Dieses Schema wird nur auf der Startseite ausgegeben, wie von [Google empfohlen](https://developers.google.com/search/docs/appearance/structured-data/organization).',
    'json_ld_entity' => 'Entität',
    'json_ld_entity_instruct' => 'Hilft Suchmaschinen, die strukturierten Daten der Website zu erkennen – und erhöht die Wahrscheinlichkeit für erweiterte Suchergebnisse (Rich Snippets).',
    'json_ld_organization_name' => 'Name der Organisation',
    'json_ld_organization_name_instruct' => 'Name der Organisation, die diese Website vertritt.',
    'json_ld_organization_logo' => 'Logo der Organisation',
    'json_ld_organization_logo_instruct' => 'Logo der Organisation, die diese Website vertritt.',
    'json_ld_person_name' => 'Name der Person',
    'json_ld_person_name_instruct' => 'Der vollständige Name der Person, die diese Website vertritt.',

    'json_ld_custom_section' => 'Benutzerdefiniertes Schema',
    'json_ld_custom_section_instruct' => 'Benutzerdefiniertes JSON-LD-Schema hinzufügen, das auf allen Seiten der Website ausgegeben wird.',
    'json_ld_schema' => 'Schema',
    'json_ld_schema_instruct' => 'Benutzerdefinierte Schema-Objekte hier einfügen (`WebSite`, `SiteNavigationElement` etc.). Wird automatisch in das passende `<script>`-Tag eingebettet.',

    'json_ld_breadcrumbs_section' => 'Breadcrumbs',
    'json_ld_breadcrumbs' => 'Breadcrumbs',
    'json_ld_breadcrumbs_instruct' => 'Breadcrumb-Strukturdaten aktivieren, damit die Position dieser Seite in der Website-Hierarchie in den Suchergebnissen angezeigt wird. [Mehr erfahren](https://developers.google.com/search/docs/appearance/structured-data/breadcrumb)',

    'robots_section' => 'Robots',
    'robots_section_instruct' => 'Festlegen, wie Suchmaschinen deine Seiten crawlen und indexieren sollen.',

    'robots_indexing' => 'Indexierung',
    'robots_indexing_instruct' => 'Festlegen, ob Suchmaschinen diese Website indexieren dürfen.',

    'robots_following' => 'Links folgen',
    'robots_following_instruct' => 'Festlegen, ob Suchmaschinen den Links auf dieser Website folgen dürfen.',

    'robots_noarchive' => 'Kein Archiv',
    'robots_noarchive_instruct' => 'Suchmaschinen daran hindern, gecachte Links dieser Website anzuzeigen.',

    'robots_noimageindex' => 'Keine Bildindexierung',
    'robots_noimageindex_instruct' => 'Suchmaschinen daran hindern, Bilder auf dieser Website zu indexieren.',

    'robots_nosnippet' => 'Kein Snippet',
    'robots_nosnippet_instruct' => 'Suchmaschinen daran hindern, Text-Snippets für diese Website anzuzeigen.',

    'image_section' => 'Open-Graph',
    'image_section_instruct' => 'Die meisten Open-Graph-Felder werden automatisch aus deinen Meta-Daten und der Website-Konfiguration generiert.',

    'image' => 'Bild',
    'image_instruct' => 'Standard-Bildfeld auswählen, das beim Teilen in sozialen Netzwerken für jede URL verwendet wird.',

    'og_type' => 'Open-Graph-Typ',
    'og_type_instruct' => 'Die Art des Inhalts (z.B. website, article). [Mehr erfahren](https://ogp.me/#types)',

    'og_title' => 'Open-Graph-Titel',
    'og_title_instruct' => 'Feld für Open-Graph-Titel auswählen.',

    'social_section' => 'X (ehemals Twitter)',
    'social_section_instruct' => 'Felder für X-Card-Titel und -Beschreibungen auswählen.',

    'twitter_handle' => 'Handle',
    'twitter_handle_instruct' => 'Handle deines X-Profils eingeben.',

    'twitter_title' => 'Card-Titel',
    'twitter_title_instruct' => 'Feld für X-Card-Titel auswählen.',

    'twitter_description' => 'Card-Beschreibung',
    'twitter_description_instruct' => 'Feld auswählen oder eigenen Wert für Standard-X-Card-Beschreibungen festlegen.',

    'sitemap_section' => 'Sitemap',
    'sitemap_section_instruct' => 'Sitemap-Einstellungen auswählen.',

    'priority' => 'Priorität',
    'priority_instruct' => 'Priorität dieser Seite in der Sitemap festlegen. Gültige Werte: `0.0` (nicht wichtig) bis `1.0` (sehr wichtig).',

    'change_frequency' => 'Änderungshäufigkeit',
    'change_frequency_instruct' => 'Festlegen, wie oft sich die Seiten voraussichtlich ändern.',

    'search_section' => 'Suchmaschinen',
    'search_section_instruct' => 'Website bei gängigen Suchmaschinen verifizieren, um das Crawling zu überwachen.',

    'bing_verification' => 'Bing-Verifizierungscode',
    'bing_verification_instruct' => 'Verifizierungscode aus den [Bing Webmaster Tools](https://www.bing.com/toolbox/webmaster) eingeben.',

    'google_verification' => 'Google-Verifizierungscode',
    'google_verification_instruct' => 'Verifizierungscode aus der [Google Search Console](https://search.google.com/search-console) eingeben.',

];
