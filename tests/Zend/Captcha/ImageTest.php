<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Captcha
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * @category   Zend
 * @package    Zend_Captcha
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Captcha
 */
class Zend_Captcha_ImageTest extends PHPUnit\Framework\TestCase
{
    protected $_tmpDir;
    protected $word;
    protected $testDir;
    protected $element;
    protected $captcha;
    protected $id;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is not available.');
            return;
        }
        if (!function_exists('imagepng')) {
            $this->markTestSkipped('Image CAPTCHA requires PNG support');
        }
        if (!function_exists('imageftbbox')) {
            $this->markTestSkipped('Image CAPTCHA requires FT fonts support');
        }

        if (isset($this->word)) {
            unset($this->word);
        }
        $this->testDir = $this->_getTmpDir() . '/ZF_test_images';
        if (!is_dir($this->testDir)) {
            @mkdir($this->testDir);
        }
        $this->element = new Zend_Form_Element_Captcha(
            'captchaI',
            array('captcha' => array('Image',
                                             'sessionClass' => 'Zend_Captcha_ImageTest_SessionContainer',
                                             'imgDir'       => $this->testDir,
                                             'font'         => dirname(__FILE__) . '/../Pdf/_fonts/Vera.ttf')
                         )
        );
        $this->captcha = $this->element->getCaptcha();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown(): void
    {
        // remove chaptcha images
        foreach (new DirectoryIterator($this->testDir) as $file) {
            if (!$file->isDot() && !$file->isDir()) {
                unlink($file->getPathname());
            }
        }
    }

    /**
     * Determine system TMP directory
     *
     * @return string
     * @throws Zend_File_Transfer_Exception if unable to determine directory
     */
    protected function _getTmpDir()
    {
        if (null === $this->_tmpDir) {
            if (function_exists('sys_get_temp_dir')) {
                $tmpdir = sys_get_temp_dir();
            } elseif (!empty($_ENV['TMP'])) {
                $tmpdir = realpath($_ENV['TMP']);
            } elseif (!empty($_ENV['TMPDIR'])) {
                $tmpdir = realpath($_ENV['TMPDIR']);
            } elseif (!empty($_ENV['TEMP'])) {
                $tmpdir = realpath($_ENV['TEMP']);
            } else {
                // Attemp to detect by creating a temporary file
                $tempFile = tempnam(md5(uniqid(rand(), true)), '');
                if ($tempFile) {
                    $tmpdir = realpath(dirname($tempFile));
                    unlink($tempFile);
                } else {
                    throw new Zend_File_Transfer_Exception('Could not determine temp directory');
                }
            }
            $this->_tmpDir = rtrim($tmpdir, '/\\');
        }
        return $this->_tmpDir;
    }

    public function getView()
    {
        $view = new Zend_View();
        $view->addHelperPath(dirname(__FILE__) . '/../../../../library/Zend/View/Helper');
        return $view;
    }

    public function testCaptchaIsRendered()
    {
        $html = $this->element->render($this->getView());
        $this->assertStringContainsString($this->element->getName(), $html);
    }

    public function testCaptchaHasIdAndInput()
    {
        $html   = $this->element->render($this->getView());
        $expect = sprintf('type="hidden" name="%s\[id\]" value="%s"', $this->element->getName(), $this->captcha->getId());
        $this->assertMatchesRegularExpression("/<input[^>]*?$expect/", $html, $html);
        $expect = sprintf('type="text" name="%s\[input\]"', $this->element->getName());
        $this->assertMatchesRegularExpression("/<input[^>]*?$expect/", $html, $html);
    }

    public function testCaptchaHasImage()
    {
        $html = $this->element->render($this->getView());
        $id   = $this->captcha->getId();
        $this->assertMatchesRegularExpression("|<img[^>]*?src=\"/images/captcha/$id.png\"|", $html, "Expected $id in HTML:\n" . $html);
    }

    public function testCaptchaHasAlt()
    {
        $html = $this->element->render($this->getView());
        $this->assertMatchesRegularExpression('|<img[^>]*? alt=""|', $html, "Expected alt= in HTML:\n" . $html);
        $this->captcha->setImgAlt('Test Image');
        $html = $this->element->render($this->getView());
        $this->assertMatchesRegularExpression('|<img[^>]*? alt="Test Image"|', $html, "Wrong alt in HTML:\n" . $html);
    }

    public function testCaptchaSetSuffix()
    {
        $this->captcha->setSuffix('.jpeg');
        $html = $this->element->render($this->getView());
        $this->assertStringContainsString('.jpeg', $html, $html);
    }

    public function testCaptchaSetImgURL()
    {
        $this->captcha->setImgURL('/some/other/URL/');
        $html = $this->element->render($this->getView());
        $this->assertStringContainsString('/some/other/URL/', $html, $html);
    }

    public function testCaptchaCreatesImage()
    {
        $this->element->render($this->getView());
        $this->assertFileExists($this->testDir . '/' . $this->captcha->getId() . '.png');
    }

    public function testCaptchaSetExpiration()
    {
        $this->assertEquals($this->captcha->getExpiration(), 600);
        $this->captcha->setExpiration(3600);
        $this->assertEquals($this->captcha->getExpiration(), 3600);
    }

    public function testCaptchaImageCleanup()
    {
        $this->element->render($this->getView());
        $filename = $this->testDir . '/' . $this->captcha->getId() . '.png';
        $this->assertFileExists($filename);
        $this->captcha->setExpiration(1);
        $this->captcha->setGcFreq(1);
        sleep(2);
        $this->captcha->generate();
        clearstatcache();
        $this->assertFileDoesNotExist($filename, "File $filename was found even after GC");
    }

    /**
     * @group ZF-10006
     */
    public function testCaptchaImageCleanupOnlyCaptchaFilesIdentifiedByTheirSuffix()
    {
        $this->element->render($this->getView());
        $filename = $this->testDir . '/' . $this->captcha->getId() . '.png';
        $this->assertFileExists($filename);
        //Create other cache file
        $otherFile = $this->testDir . '/zf10006.cache';
        file_put_contents($otherFile, '');
        $this->assertFileExists($otherFile);
        $this->captcha->setExpiration(1);
        $this->captcha->setGcFreq(1);
        sleep(2);
        $this->captcha->generate();
        clearstatcache();
        $this->assertFileDoesNotExist($filename, "File $filename was found even after GC");
        $this->assertFileExists($otherFile, "File $otherFile was not found after GC");
    }

    public function testGenerateReturnsId()
    {
        $id = $this->captcha->generate();
        $this->assertNotEmpty($id);
        $this->assertIsString($id);
        $this->id = $id;
    }

    public function testGetWordReturnsWord()
    {
        $this->captcha->generate();
        $word = $this->captcha->getWord();
        $this->assertNotEmpty($word);
        $this->assertIsString($word);
        $this->assertTrue(strlen($word) == 8);
        $this->word = $word;
    }

    public function testGetWordLength()
    {
        $this->captcha->setWordLen(4);
        $this->captcha->generate();
        $word = $this->captcha->getWord();
        $this->assertIsString($word);
        $this->assertTrue(strlen($word) == 4);
        $this->word = $word;
    }

    public function testAdapterElementName()
    {
        $this->assertEquals(
            $this->captcha->getName(),
            $this->element->getName()
        );
    }

    public function testGenerateIsRandomised()
    {
        $id1   = $this->captcha->generate();
        $word1 = $this->captcha->getWord();
        $id2   = $this->captcha->generate();
        $word2 = $this->captcha->getWord();

        $this->assertNotEmpty($id1);
        $this->assertNotEmpty($id2);
        $this->assertFalse($id1 == $id2);
        $this->assertFalse($word1 == $word2);
    }

    public function testRenderSetsValue()
    {
        $this->testCaptchaIsRendered();
        $this->assertEquals(
            $this->captcha->getId(),
            $this->element->getValue()
        );
    }

    public function testLabelIsNull()
    {
        $this->assertNull($this->element->getLabel());
    }

    public function testRenderInitializesSessionData()
    {
        $this->testCaptchaIsRendered();
        $session = $this->captcha->getSession();
        $this->assertEquals($this->captcha->getTimeout(), $session->setExpirationSeconds);
        $this->assertEquals(1, $session->setExpirationHops);
        $this->assertEquals($this->captcha->getWord(), $session->word);
    }

    public function testWordValidates()
    {
        $this->testCaptchaIsRendered();
        $input = array($this->element->getName() => array('id' => $this->captcha->getId(), 'input' => $this->captcha->getWord()));
        $this->assertTrue($this->element->isValid('', $input));
    }

    public function testMissingNotValid()
    {
        $this->testCaptchaIsRendered();
        $this->assertFalse($this->element->isValid('', array()));
        $input = array($this->element->getName() => array('input' => 'blah'));
        $this->assertFalse($this->element->isValid('', $input));
    }

    public function testWrongWordNotValid()
    {
        $this->testCaptchaIsRendered();
        $input = array($this->element->getName() => array('id' => $this->captcha->getId(), 'input' => 'blah'));
        $this->assertFalse($this->element->isValid('', $input));
    }

    /**
     * @group ZF-3995
     */
    public function testIsValidShouldAllowPassingArrayValueWithNoContext()
    {
        $this->testCaptchaIsRendered();
        $input = array($this->element->getName() => array('id' => $this->captcha->getId(), 'input' => $this->captcha->getWord()));
        $this->assertTrue($this->element->isValid($input));
    }

    /**
     * @group ZF-3995
     */
    public function testIsValidShouldNotRequireValueToBeNestedArray()
    {
        $this->testCaptchaIsRendered();
        $input = array('id' => $this->captcha->getId(), 'input' => $this->captcha->getWord());
        $this->assertTrue($this->element->isValid($input));
    }

    /**
     * @group ZF-11483
     */
    public function testImageTagRenderedProperlyBasedUponDoctype()
    {
        $this->testCaptchaIsRendered();
        $view = new Zend_View();

        $view->doctype('XHTML1_STRICT');
        $this->assertMatchesRegularExpression('#/>$#', $this->captcha->render($view));

        $view->doctype('HTML4_STRICT');
        $this->assertMatchesRegularExpression('#[^/]>$#', $this->captcha->render($view));
    }
}

#[AllowDynamicProperties]
class Zend_Captcha_ImageTest_SessionContainer
{
    protected static $_word;
    protected $name;

    public function __get($name)
    {
        if ('word' == $name) {
            return self::$_word;
        }

        return null;
    }

    public function __set($name, $value)
    {
        if ('word' == $name) {
            self::$_word = $value;
        } else {
            $this->$name = $value;
        }
    }

    public function __isset($name)
    {
        if (('word' == $name) && (null !== self::$_word)) {
            return true;
        }

        return false;
    }

    public function __call($method, $args)
    {
        switch ($method) {
            case 'setExpirationHops':
            case 'setExpirationSeconds':
                $this->$method = array_shift($args);
                break;
            default:
        }
    }
}
