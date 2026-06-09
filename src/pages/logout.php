<?php
declare(strict_types = 1);

$cms->getSession()->delete();

header('Location: ' . DOC_ROOT);
exit;