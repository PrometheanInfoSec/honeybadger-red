#!/usr/bin/env python

import sys
import random
import string
import hashlib
import sqlite3

try:
    username = raw_input('Username: ')
    password = raw_input('Password: ')
    print '  User Role Options:'
    print '  0 - Administrator'
    print '  1 - User'
    role = int(raw_input('Role: '))
    if not role in [0,1]:
        print '[!] Invalid role.'
        sys.exit()
    salt = ''.join(random.choice(string.letters) for i in range(5))
    print 'Salt: %s' % (salt)
    hash = hashlib.sha1(salt+password).hexdigest()
    print 'Hash: %s' % (hash)
    conn = sqlite3.connect('../data/data.db')
    cur = conn.cursor()
    query = u'INSERT INTO users VALUES (?, ?, ?, ?)'
    cur.execute(query, (username, hash, salt, role))
    conn.commit()
    conn.close()
    print 'User \'%s\' added.' % (username)
except sqlite3.IntegrityError:
    print '[!] User \'%s\' already exists.' % (username)
except sqlite3.OperationalError:
    print '[!] Database not initialized.'
except KeyboardInterrupt:
    print ''
