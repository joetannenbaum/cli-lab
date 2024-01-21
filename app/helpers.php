<?php

/**
 * Multi-byte version of wordwrap.
 *
 * @param non-empty-string $break
 */
if (!function_exists('mb_wordwrap')) {
    function mb_wordwrap(
        string $string,
        int $width = 75,
        string $break = "\n",
        bool $cut_long_words = false
    ): string {
        $lines = explode($break, $string);
        $result = [];

        foreach ($lines as $originalLine) {
            if (mb_strwidth($originalLine) <= $width) {
                $result[] = $originalLine;
                continue;
            }
            $words = explode(' ', $originalLine);
            $line = null;
            $lineWidth = 0;

            if ($cut_long_words) {
                foreach ($words as $index => $word) {
                    $characters = mb_str_split($word);
                    $strings = [];
                    $str = '';

                    foreach ($characters as $character) {
                        $tmp = $str . $character;

                        if (mb_strwidth($tmp) > $width) {
                            $strings[] = $str;
                            $str = $character;
                        } else {
                            $str = $tmp;
                        }
                    }

                    if ($str !== '') {
                        $strings[] = $str;
                    }

                    $words[$index] = implode(' ', $strings);
                }

                $words = explode(' ', implode(' ', $words));
            }

            foreach ($words as $word) {
                $tmp = ($line === null) ? $word : $line . ' ' . $word;

                // Look for zero-width joiner characters (combined emojis)
                preg_match(' /\p{Cf}/u', $word, $joinerMatches);
                $wordWidth = count($joinerMatches) > 0 ? 2 : mb_strwidth($word);

                $lineWidth += $wordWidth;

                if ($line !== null) {
                    // Space between words
                    $lineWidth += 1;
                }

                if ($lineWidth <= $width) {
                    $line = $tmp;
                } else {
                    $result[] = $line;
                    $line = $word;
                    $lineWidth = $wordWidth;
                }
            }
            if ($line !== '') {
                $result[] = $line;
            }
            $line = null;
        }
        return implode($break, $result);
    }
}
