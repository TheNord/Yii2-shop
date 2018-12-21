test:
	vagrant ssh -- 'cd /app && /usr/bin/php /app/vendor/bin/codecept run unit -- -c common'