<?php
namespace source;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Klasse um einen Autoloader zu erzeugen
 *
 * @author Stefan Kuhn
 */
RIOAutoloader::register();

class RIOAutoloader {

    private static ?RIOAutoloader $autoloader = null;
    private static string $filePath;
    private static string $root;

    public function __construct() {
        spl_autoload_register(array($this, '__autoload'));
        self::$root = __DIR__;
        self::$filePath = self::$root . '/RIOClasses.php';
        $this->initClassFile();
        include(self::$filePath);
    }

    /*
     * Erzeugt einen Autoloader, wenn es noch keinen gibt.
     */

    public static function register(): void
    {
        if (self::$autoloader === null) {
            self::$autoloader = new RIOAutoloader();
        }
    }

    /**
     * Wenn man eine Klasse verwenden will und PHP sie nicht findet wird 
     * <code>function __autoload($className)</code> aufgerufen um die 
     * Klasse zu inkludieren. Da die Namen der Klassen mit den Dateinamen 
     * übereinstimmen, ist die Logik dahinter sehr einfach gehalten.
     * 
     * @param string $className Der Name der Klasse die gesucht werden soll.
     */
    function __autoload(string $className): void
    {
        


        if (isset($GLOBALS[$className])) {
            if (file_exists($GLOBALS[$className])) {
                include_once($GLOBALS[$className]);
                return;
            } else {
                unlink(self::$filePath);
                $this->initClassFile();
            }
        }

        /*
         * <code>RecursiveDirectoryIterator</code> enthält einen Array der 
         * unter anderem den Dateipfad enthält. 
         * <code>RecursiveIteratorIterator</code> geht durch diesen Pfad
         * 
         * Hauptverzeichnis des Projektes auf dem Server 
         * <code>$_SERVER['DOCUMENT_ROOT']</code>
         */
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$root));
        foreach ($rii as $file) {
            /*
             * Wenn <code>$file</code> kein Verzeichnis ist
             */
            if (!$file->isDir()) {
                /*
                 * Wenn <code>$file</code> (ohne .php) denselben Inhalt wie
                 * <code>$className</code> hat wird <code>$file</code> inkludiert.
                 * 
                 */
                if ($file->getBaseName('.php') === $className) {
                    $this->saveToFile($className, $file);
                    include_once($file);
                }
            }
        }
    }

    private function saveToFile(string $className, string $classPath): void
    {
        file_put_contents(self::$filePath, '$GLOBALS[\'' . $className . '\']=\'' . $classPath . '\';' . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function initClassFile(): void
    {
        if (!file_exists(self::$filePath)) {
            file_put_contents(self::$filePath, '<?php' . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

}
