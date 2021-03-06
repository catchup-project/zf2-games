<?php

namespace SecretaryTest\Service;

use Secretary\Service\Encryption as EncryptionService;

class EncryptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $passphrase = '9xt^#]E#RqEW6NkZEKRw';

    /**
     * @var string
     */
    protected $content = 'Hello world!';

    /**
     * @var string
     */
    protected $ekey = 'A9Ts2Z+zOCjYM8d2d1PnOIt2AmTDMpwM0WPLJ/ZH8P/S1VhvDFV+1ztkVCOPWOf8UkH1z1EzQ7TjxnjHtULhtHQm/ZfBXB0uaxu71QjnqVWs0fpeMrT5MEVOijaXkeBBzz4sqwd5AF1gBwMnYD/jgOqpiPxwtb8NyrR5+ESHMeyjULlpJyoNyIWZ/pZOQzWjVl8JvZX2DItTJoQUJLArAHV0YCGFn4S+F1rXHiOxF5rmV5572CThuxRjDtXGCQQym0jSx59gjAWkHPMiwH5KHPQjdvnMRTI+2C74N97u2gzx0OzOzT8T0+Zf22IaBivHcACBRoMdVF30fWchtwOhGw==';

    /**
     * @var string
     */
    protected $contentEncrypted = 'BS+teeDwWOP0Yg3LjG6h0FlnxJg=';

    /**
     * @var \Secretary\Service\Encryption
     */
    protected $encryptionService;

    protected function setUp()
    {
        parent::setUp();
        $this->encryptionService = new EncryptionService();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->encryptionService);
    }

    public function testCreatePrivateKey()
    {
        $response = $this->encryptionService->createPrivateKey($this->passphrase);

        $this->assertArrayHasKey('pub', $response);
        $this->assertArrayHasKey('priv', $response);
        $this->assertNotEmpty($response['pub']);
        $this->assertNotEmpty($response['priv']);

        $pk1 = openssl_pkey_get_public($response['pub']);
        $this->assertNotSame(false, $pk1);
        $pubKey = openssl_pkey_get_details($pk1);
        $this->assertSame($pubKey['key'], $response['pub']);

        $pk2 = openssl_pkey_get_private($response['priv'], $this->passphrase);
        $this->assertNotSame(false, $pk2);
        $pubKey = openssl_pkey_get_details($pk1);
        $this->assertSame($pubKey['key'], $response['pub']);

        openssl_free_key($pk1);
        openssl_free_key($pk2);
    }

    public static function emptyProvider()
    {
        return array(
            array(null),
            array(''),
            array(0),
        );
    }

    /**
     * @dataProvider emptyProvider
     * @expectedException \InvalidArgumentException
     */
    public function testCreatePrivateKeyException($passphrase)
    {
        $this->encryptionService->createPrivateKey($passphrase);
    }

    /**
     * @dataProvider emptyProvider
     * @expectedException \InvalidArgumentException
     */
    public function testValidateKeyArgumentException($key)
    {
        $this->encryptionService->validateKey($key, $this->passphrase);
    }

    /**
     * @expectedException \LogicException
     */
    public function testValidateKeyLogicException()
    {
        $key = 'abc';
        $this->encryptionService->validateKey($key, $this->passphrase);
    }

    public function testValidateKey()
    {
        $key   = file_get_contents(dirname(__DIR__) . '/../keys/private.pem');
        $check = $this->encryptionService->validateKey($key, $this->passphrase);
        $this->assertTrue($check);
        unset($key);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEncryptForMultipleKeysArgumentException()
    {
        $this->encryptionService->encryptForMultipleKeys($this->content, array());
    }
    /**
     * @expectedException \LogicException
     */
    public function testEncryptForMultipleKeysLogicException()
    {
        $key1 = 'abc';
        $key2 = 'xyz';
        $this->encryptionService->encryptForMultipleKeys($this->content, array($key1, $key2));
    }

    public function testEncryptForMultipleKeys()
    {
        $key1      = file_get_contents(dirname(__DIR__) . '/../keys/public.pem');
        $key2      = file_get_contents(dirname(__DIR__) . '/../keys/public2.pem');
        $response = $this->encryptionService->encryptForMultipleKeys(
            $this->content, array($key1, $key2)
        );

        $this->assertArrayHasKey('ekeys', $response);
        $this->assertArrayHasKey('content', $response);
        $this->assertNotEmpty($response['ekeys']);
        $this->assertNotEmpty($response['content']);
    }

    /**
     * @dataProvider emptyProvider
     * @expectedException \InvalidArgumentException
     */
    public function testEncryptForSingleKeyArgumentException($key)
    {
        $this->encryptionService->encryptForSingleKey($this->content, $key);
    }

    /**
     * @expectedException \LogicException
     */
    public function testEncryptForSingleKeyLogicException()
    {
        $key = 'abc';
        $this->encryptionService->encryptForSingleKey($this->content, $key);
    }

    public function testEncryptForSingleKey()
    {
        $key      = file_get_contents(dirname(__DIR__) . '/../keys/public.pem');
        $response = $this->encryptionService->encryptForSingleKey($this->content, $key);

        $this->assertArrayHasKey('ekey', $response);
        $this->assertArrayHasKey('content', $response);
        $this->assertNotEmpty($response['ekey']);
        $this->assertNotEmpty($response['content']);
    }


    /**
     * @dataProvider emptyProvider
     * @expectedException \InvalidArgumentException
     */
    public function testDecryptArgumentException1($empty)
    {
        $this->encryptionService->decrypt(
            $empty,
            $this->ekey,
            $empty,
            $this->passphrase
        );
    }

    /**
     * @dataProvider emptyProvider
     * @expectedException \InvalidArgumentException
     */
    public function testDecryptArgumentException2($empty)
    {
        $this->encryptionService->decrypt(
            $this->contentEncrypted,
            $empty,
            $empty,
            $this->passphrase
        );
    }

    /**
     * @dataProvider emptyProvider
     * @expectedException \InvalidArgumentException
     */
    public function testDecryptArgumentException3($empty)
    {
        $this->encryptionService->decrypt(
            $this->contentEncrypted,
            $this->ekey,
            $empty,
            $this->passphrase
        );
    }

    /**
     * @dataProvider emptyProvider
     * @expectedException \InvalidArgumentException
     */
    public function testDecryptArgumentException4($empty)
    {
        $key = 'abc';
        $this->encryptionService->decrypt(
            $this->contentEncrypted,
            $this->ekey,
            $key,
            $empty
        );
    }

    public static function decryptProvider()
    {
        $privKey = file_get_contents(dirname(__DIR__) . '/../keys/private.pem');
        return array(
            array('abc', '9xt^#]E#RqEW6NkZEKRw'),
            array($privKey, 'abc'),
        );
    }

    /**
     * @dataProvider decryptProvider
     * @expectedException \LogicException
     */
    public function testDecryptLogicExceptionWrongKeyPassphraseCombo($key, $passphrase)
    {
        $this->encryptionService->decrypt(
            $this->contentEncrypted,
            $this->ekey,
            $key,
            $passphrase
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testDecryptLogicExceptionWrongEkey()
    {
        $privKey = file_get_contents(dirname(__DIR__) . '/../keys/private.pem');
        $this->encryptionService->decrypt(
            $this->contentEncrypted,
            'hohoho',
            $privKey,
            $this->passphrase
        );
    }

    public function testDecrypt()
    {
        $privKey  = file_get_contents(dirname(__DIR__) . '/../keys/private.pem');
        $content = $this->encryptionService->decrypt(
            $this->contentEncrypted,
            $this->ekey,
            $privKey,
            $this->passphrase
        );
        $this->assertSame($this->content, $content);
    }

}