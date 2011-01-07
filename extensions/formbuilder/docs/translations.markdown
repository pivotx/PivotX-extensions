# Translations

The messages are automaticaly translated if a translation is available.

To enable another translation, make sure that the translation file exists in `formbuilder/translations`. 
The name for a translation file should be {language code}.php where {language code} is something like en, de, fr or nl.

To enable a translation add a key called 'formbuilder_language' in your pivotx advanced configuration and set the value to the {language code}

If you want to customize a default translation - copy the translation file to `formbuilder/overrides/translations/{language code}.php` and replace the translated texts with your own. The rest will happen automatically.

Translation is simple, add the english text and the translation on a line in the array like `"{english text}"=>"{custom translation}",`.