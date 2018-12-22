tests-unit:
	vagrant ssh -- 'cd /app && /usr/bin/php /app/vendor/bin/codecept run unit -- -c common'

migrate:
	vagrant ssh -- 'cd /app && /usr/bin/php yii migrate -- -c common'

migrate-test:
	vagrant ssh -- 'cd /app && /usr/bin/php yii_test migrate -- -c common'