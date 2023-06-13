# Atmlabs symfony 2.8

Static version of symfony for prestashop 1.7. I modified the namespace to be able to use it alongside a newest version of sf.

The namespace is symfony2 to avoid conflict, every class namesapces and import have been updated, and each composer.json
accordingly (when requiring a symfony package present in our root composer "replace" section it has been updated to symfony2).

Further test is needed to ensure this does not cause any issue.

#### Notes

- Added security-acl to components (was not included initially)
