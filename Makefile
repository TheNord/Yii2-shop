tests-unit:
	vagrant ssh -- 'cd /app && /usr/bin/php /app/vendor/bin/codecept run unit -- -c common'

migrate:
	yes | vagrant ssh -- 'cd /app && /usr/bin/php yii migrate -- -c common'

migrate-test:
	yes | vagrant ssh -- 'cd /app && /usr/bin/php yii_test migrate -- -c common'