.PHONY: queue dev

queue:
	php artisan queue:work --queue=wordpress,default --tries=3 --timeout=310

dev:
	php artisan serve & pnpm run dev
