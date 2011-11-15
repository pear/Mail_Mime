
clean:
	rm -f *.log *.php *.diff *.exp *.out



test:
	cd .. && pear run-tests -i "-d error_reporting=22527" tests/*.phpt && cd tests;
