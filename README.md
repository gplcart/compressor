[![Build Status](https://scrutinizer-ci.com/g/gplcart/compressor/badges/build.png?b=master)](https://scrutinizer-ci.com/g/gplcart/compressor/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gplcart/compressor/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gplcart/compressor/?branch=master)

Compressor is a [GPL Cart](https://github.com/gplcart/gplcart) module that allows to aggregate JS and CSS files.

**Installation**

1. Download and extract to `system/modules` manually or using composer `composer require gplcart/compressor`. IMPORTANT: If you downloaded the module manually, be sure that the name of extracted module folder doesn't contain a branch/version suffix, e.g `-master`. Rename if needed.
2. Go to `admin/module/list` end enable the module
3. Enable aggregation at `admin/module/settings/compressor`