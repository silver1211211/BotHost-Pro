@include('errors.layout', [
    'code'    => '429',
    'title'   => 'Too Many Requests',
    'message' => 'You\'ve made too many requests in a short period. Please slow down and try again in a few minutes.',
])
