- Path: /hello/{name}
- Path Regex: #PATH_REGEX#
- Host: localhost
- Host Regex: #HOST_REGEX#
- Scheme: http|https
- Method: GET|HEAD
- Class: Symfony2\Bundle\FrameworkBundle\Tests\Console\Descriptor\RouteStub
- Defaults:
    - `name`: Joseph
- Requirements:
    - `name`: [a-z]+
- Options:
    - `compiler_class`: Symfony2\Component\Routing\RouteCompiler
    - `opt1`: val1
    - `opt2`: val2
