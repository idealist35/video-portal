<?php
/**
 * Site localisation.
 *
 * English is the default and Arabic is the alternative. The choice is explicit:
 * it arrives as ?lang= from the header switch, is stored in a cookie, and the
 * request is redirected back to the clean URL so the parameter never sticks to
 * links people share. Accept-Language is deliberately not consulted, so a
 * visitor always lands on English until they ask for something else.
 */

const LOCALE_COOKIE = 'hikaya_lang';
const DEFAULT_LOCALE = 'en';
const LOCALE_COOKIE_LIFETIME = 31536000; // one year

/**
 * @return array<string, array<string, string>>
 */
function availableLocales(): array
{
    return [
        'en' => [
            'label'  => 'English',
            'native' => 'English',
            'short'  => 'EN',
            'dir'    => 'ltr',
            'html'   => 'en',
        ],
        'ar' => [
            'label'  => 'Arabic',
            'native' => 'العربية',
            // Shown in the header switch. Arabic script rather than "AR", so an
            // Arabic reader recognises their own language at a glance.
            'short'  => 'عربي',
            'dir'    => 'rtl',
            'html'   => 'ar',
        ],
    ];
}

function isSupportedLocale(string $locale): bool
{
    return isset(availableLocales()[$locale]);
}

/**
 * Resolve the locale for this request and remember an explicit choice.
 *
 * Called once from the front controller before anything is written to the
 * response, because both the cookie and the redirect need the headers.
 */
function initLocale(): string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $requested = (string) ($_GET['lang'] ?? '');
    if ($requested !== '' && isSupportedLocale($requested)) {
        setLocaleCookie($requested);
        $resolved = $requested;
        redirectWithoutLangParam();

        return $resolved;
    }

    $stored = (string) ($_COOKIE[LOCALE_COOKIE] ?? '');
    $resolved = isSupportedLocale($stored) ? $stored : DEFAULT_LOCALE;

    return $resolved;
}

function currentLocale(): string
{
    return initLocale();
}

function localeDirection(): string
{
    return availableLocales()[currentLocale()]['dir'] ?? 'ltr';
}

function isRtl(): bool
{
    return localeDirection() === 'rtl';
}

function setLocaleCookie(string $locale): void
{
    if (headers_sent()) {
        return;
    }

    setcookie(LOCALE_COOKIE, $locale, [
        'expires'  => time() + LOCALE_COOKIE_LIFETIME,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[LOCALE_COOKIE] = $locale;
}

/**
 * Send the visitor back to the page they were on, minus ?lang=.
 *
 * Keeping the parameter would leave it on every shared link and split the same
 * page across two URLs for no benefit, since the cookie already carries the
 * choice forward.
 */
function redirectWithoutLangParam(): void
{
    if (headers_sent()) {
        return;
    }

    $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $query = $_GET;
    unset($query['lang']);
    $queryString = http_build_query($query);

    header('Cache-Control: private, no-store');
    header('Location: ' . $path . ($queryString !== '' ? '?' . $queryString : ''), true, 302);
    exit;
}

/**
 * The URL that switches to a given locale, staying on the current page.
 */
function localeSwitchUrl(string $locale): string
{
    $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $query = $_GET;
    $query['lang'] = $locale;

    return $path . '?' . http_build_query($query);
}

/**
 * @return array<string, mixed>
 */
function loadCatalog(string $locale): array
{
    static $catalogs = [];
    if (isset($catalogs[$locale])) {
        return $catalogs[$locale];
    }

    $file = BASE_PATH . '/includes/lang/' . $locale . '.php';
    $catalogs[$locale] = is_file($file) ? (array) require $file : [];

    return $catalogs[$locale];
}

/**
 * Translate a key, falling back to English and then to the key itself.
 *
 * A missing key renders as the key rather than an empty box, so a gap in a
 * catalogue is obvious on the page instead of silently deleting a label.
 *
 * @param array<string, string|int> $params replacements for {placeholders}
 */
function t(string $key, array $params = []): string
{
    $value = loadCatalog(currentLocale())[$key]
        ?? loadCatalog(DEFAULT_LOCALE)[$key]
        ?? $key;

    if (is_array($value)) {
        // A plural entry reached t() without a count; use the generic form.
        $value = $value['other'] ?? $value['one'] ?? $key;
    }

    return interpolate((string) $value, $params);
}

/**
 * Translate a counted string using CLDR plural categories.
 *
 * Arabic distinguishes six of them, so "2 episodes" and "11 episodes" are not
 * the same word shape; picking a form by `$count === 1` would be wrong on most
 * numbers this site actually shows.
 *
 * @param array<string, string|int> $params
 */
function tn(string $key, int $count, array $params = []): string
{
    $entry = loadCatalog(currentLocale())[$key] ?? loadCatalog(DEFAULT_LOCALE)[$key] ?? null;
    if ($entry === null) {
        return $key;
    }

    $params += ['count' => formatNumber($count)];

    if (!is_array($entry)) {
        return interpolate((string) $entry, $params);
    }

    $category = pluralCategory(currentLocale(), $count);
    $value = $entry[$category] ?? $entry['other'] ?? $entry['one'] ?? $key;

    return interpolate((string) $value, $params);
}

function pluralCategory(string $locale, int $count): string
{
    if ($locale !== 'ar') {
        return $count === 1 ? 'one' : 'other';
    }

    if ($count === 0) {
        return 'zero';
    }
    if ($count === 1) {
        return 'one';
    }
    if ($count === 2) {
        return 'two';
    }

    $mod100 = $count % 100;
    if ($mod100 >= 3 && $mod100 <= 10) {
        return 'few';
    }
    if ($mod100 >= 11 && $mod100 <= 99) {
        return 'many';
    }

    return 'other';
}

/**
 * @param array<string, string|int> $params
 */
function interpolate(string $value, array $params): string
{
    if (empty($params)) {
        return $value;
    }

    $replacements = [];
    foreach ($params as $name => $replacement) {
        $replacements['{' . $name . '}'] = (string) $replacement;
    }

    return strtr($value, $replacements);
}

/**
 * Render a number in the digits the locale reads.
 *
 * Arabic-language sites use both Western and Eastern Arabic numerals; Western
 * digits are kept because every episode number, duration and season label on
 * this site is generated from the same data, and mixing the two sets within one
 * page reads worse than choosing one.
 */
function formatNumber(int $number): string
{
    return (string) $number;
}

/**
 * Escaped translation, for values dropped straight into markup.
 *
 * @param array<string, string|int> $params
 */
function te(string $key, array $params = []): string
{
    return htmlspecialchars(t($key, $params), ENT_QUOTES, 'UTF-8');
}
