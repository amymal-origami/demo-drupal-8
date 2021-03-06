<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\PhpStorage\FileStorageReadOnlyTest.
 */

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\PhpStorage\FileStorage;
use Drupal\Component\PhpStorage\FileReadOnlyStorage;

/**
 * @coversDefaultClass \Drupal\Component\PhpStorage\FileReadOnlyStorage
 *
 * @group Drupal
 * @group PhpStorage
 */
class FileStorageReadOnlyTest extends PhpStorageTestBase {

  /**
   * Standard test settings to pass to storage instances.
   *
   * @var array
   */
  protected $standardSettings;

  /**
   * Read only test settings to pass to storage instances.
   *
   * @var array
   */
  protected $readonlyStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->standardSettings = array(
      'directory' => $this->directory,
      'bin' => 'test',
    );
    $this->readonlyStorage = array(
      'directory' => $this->directory,
      // Let this read from the bin where the other instance is writing.
      'bin' => 'test',
    );
  }

  /**
   * Tests writing with one class and reading with another.
   */
  public function testReadOnly() {
    $php = new FileStorage($this->standardSettings);
    $name = $this->randomMachineName() . '/' . $this->randomMachineName() . '.php';

    // Find a global that doesn't exist.
    do {
      $random = mt_rand(10000, 100000);
    } while (isset($GLOBALS[$random]));

    // Write out a PHP file and ensure it's successfully loaded.
    $code = "<?php\n\$GLOBALS[$random] = TRUE;";
    $success = $php->save($name, $code);
    $this->assertSame($success, TRUE);
    $php_read = new FileReadOnlyStorage($this->readonlyStorage);
    $php_read->load($name);
    $this->assertTrue($GLOBALS[$random]);

    // If the file was successfully loaded, it must also exist, but ensure the
    // exists() method returns that correctly.
    $this->assertSame($php_read->exists($name), TRUE);
    // Saving and deleting should always fail.
    $this->assertFalse($php_read->save($name, $code));
    $this->assertFalse($php_read->delete($name));
  }

  /**
   * @covers ::writeable
   */
  public function testWriteable() {
    $php_read = new FileReadOnlyStorage($this->readonlyStorage);
    $this->assertFalse($php_read->writeable());
  }

  /**
   * @covers ::deleteAll
   */
  public function testDeleteAll() {
    $php = new FileStorage($this->standardSettings);
    $name = $this->randomMachineName() . '/' . $this->randomMachineName() . '.php';

    // Find a global that doesn't exist.
    do {
      $random = mt_rand(10000, 100000);
    } while (isset($GLOBALS[$random]));

    // Write our the file so we can test deleting.
    $code = "<?php\n\$GLOBALS[$random] = TRUE;";
    $this->assertTrue($php->save($name, $code));

    $php_read = new FileReadOnlyStorage($this->readonlyStorage);
    $this->assertFalse($php_read->deleteAll());

    // Make sure directory exists prior to removal.
    $this->assertTrue(file_exists($this->directory . '/test'), 'File storage directory does not exist.');
  }

}
