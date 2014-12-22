# -*- coding: utf-8 -*-
# /* / */
# basic script to add statements with item values on Wikidata items with Qitem-Property-Qvalue
# data form in txt file:
# Q4115189[TAB]P180[TAB]Q235113

import pywikibot
site = pywikibot.Site("wikidata", "wikidata")
repo = site.data_repository()
f_in = open('data.txt', 'r')

# reference (here imported from Wikipedia [en])
REF = pywikibot.Claim(repo,'P143')
REF.setTarget(pywikibot.ItemPage(repo,'Q328'))

for line in f_in:
    info = line.split("	")
    item = pywikibot.ItemPage(repo,info[0])
    claim = pywikibot.Claim(repo,info[1])

    # test if property/value is already in statement
    already=0
    if claim.getID() in item.get().get('claims'):
        propertytoadd = claim.getID()
        for valueofproperty in item.claims[propertytoadd]:
            if valueofproperty.getTarget().getID()==info[2]:
                already=1
                break

    # edit
    if already==0:
        target = pywikibot.ItemPage(repo,info[2])
        claim.setTarget(target)
        item.addClaim(claim)
        claim.addSource(REF)
       