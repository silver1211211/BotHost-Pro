<?php

test('public legal pages can be rendered', function () {
    foreach ([
        '/privacy-policy',
        '/terms',
        '/cookie-policy',
        '/refund-policy',
        '/acceptable-use',
    ] as $path) {
        $this->get($path)->assertOk();
    }
});
