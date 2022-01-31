<?php return array(
    'root' => array(
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'type' => 'library',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => '__root__',
        'dev' => true,
    ),
    'versions' => array(
        '__root__' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
        'lifo/ip' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'type' => 'library',
            'install_path' => __DIR__ . '/../lifo/ip',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'reference' => 'b6a36dab288d7aea155698808bfc6649799fe413',
            'dev_requirement' => false,
        ),
        'mpdroog/core' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'type' => 'library',
            'install_path' => __DIR__ . '/../mpdroog/core',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'reference' => '427a2bef6204910cc28d0e3db0de9ffccdef8dcb',
            'dev_requirement' => false,
        ),
        'pda/pheanstalk' => array(
            'pretty_version' => 'v3.x-dev',
            'version' => '3.9999999.9999999.9999999-dev',
            'type' => 'library',
            'install_path' => __DIR__ . '/../pda/pheanstalk',
            'aliases' => array(),
            'reference' => '5614ef449fd3d4c3b82fbf72f085357c9b4c77ed',
            'dev_requirement' => false,
        ),
    ),
);
