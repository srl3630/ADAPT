#########################################################
# Author: Alexander Kellermann Nieves
# Date: Feb 9th, 2019
# Instructions for how to use data set found at:
# https://github.com/mitre/cti/blob/master/USAGE.md
# Source of MITRE CTI is at:
# https://github.com/mitre/cti/
# But we're focused on the enterprise-attack only.
#########################################################

from stix2 import FileSystemSource
from stix2 import Filter
from stix2.utils import get_type_from_id
import random
import parse_csv
import itertools


def get_all_techniques(src):
    filt = [Filter('type', '=', 'attack-pattern')]
    return src.query(filt)


def get_technique_by_name(src, name):
    filt = [
        Filter('type', '=', 'attack-pattern'),
        Filter('name', '=', name)
    ]
    return src.query(filt)


def get_techniques_by_content(src, content):
    techniques = get_all_techniques(src)
    return [
        tech for tech in techniques
        if content.lower() in tech.description.lower()
    ]


def get_object_by_attack_id(src, typ, attack_id):
    filt = [
        Filter('type', '=', typ),
        Filter('external_references.external_id', '=', attack_id)
    ]
    return src.query(filt)


def get_group_by_alias(src, alias):
    return src.query([
        Filter('type', '=', 'intrusion-set'),
        Filter('aliases', '=', alias)
    ])


def get_all_groups(src):
    return src.query([
        Filter('type', '=', 'intrusion-set')
    ])


def get_technique_by_group(src, stix_id):
    relations = src.relationships(stix_id, 'uses', source_only=True)
    return src.query([
        Filter('type', '=', 'attack-pattern'),
        Filter('id', 'in', [r.target_ref for r in relations])
    ])


def generate_random_data(matrix):
    temp_list = []
    flat_list = []
    for key in matrix.keys():
        matrix[key] = list(filter(None, matrix[key]))
        temp_list.append(matrix[key])

    return list(itertools.chain.from_iterable(temp_list))


def get_technique_users(src, tech_stix_id):
    groups = [
        r.source_ref
        for r in src.relationships(tech_stix_id, 'uses', target_only=True)
        if get_type_from_id(r.source_ref) == 'intrusion-set'
    ]

    return src.query([
        Filter('type', 'in', 'intrusion-set'),
        Filter('id', 'in', groups)
    ])


def classify_list():
    pass


def main():
    # If you're reading this, good luck.
    # Below you'll see a file system object that's based on enterprise attack
    # In order to understand what's happening here, go to the link at the top
    # of this file. This stuff all has to do with STIX2. Basically, the group
    # that worked on this before me didn't associate APT with the techniques.
    # So I glued this together because I didn't know any php and no one contributed
    # to the code. This does everything you'd need. But to help you avoid needing
    # to run this, I've included the 'groups_techniques.txt' file which is the output
    # of this program when set up and ran correctly. Assuming you don't care about
    # the latest data, that file should suffice.
    # The format of that file is
    # ![GROUPNAME]
    # [List of techniques, seperated by commas (or newline if there are none ]
    fs = FileSystemSource('cti-master/enterprise-attack')
    groups = get_all_groups(fs)
    for group in groups:
        print('%' + group.name)
        for item in get_technique_by_group(fs, group):
            if item.name:
                print(item.name + ',', end='')
            else:
                pass
        print("\n===========================")

def test_stuff():
    # Set up the source of the information for all the queries
    fs = FileSystemSource('cti-master/enterprise-attack')

    """ Listed below are some example usages of the methods contained in this file
    - y = get_technique_users(fs, 'attack-pattern--62b8c999-dcc0-4755-bd69-09442d9359f5')
    - get_techniques_by_content(fs, 'rundll32.exe')
    - get_object_by_attack_id(fs, 'intrusion-set', 'G0016')
    - get_group_by_alias(fs, 'Cozy Bear')[0]
    - group = get_group_by_alias(fs, 'Cozy Bear')[0]
    - print(type(get_technique_by_group(fs, group)))
    - print(type(get_technique_by_name(fs, 'Valid Accounts')[0]))
    """

    groups = get_all_groups(fs)
    group_dict = {}
    for x in groups:
        try:
            if x.aliases:  # This is a list, so there can be multiple aliases contained.
                for alias in x.aliases:
                    if alias not in group_dict:
                        group_dict[alias] = 0
        except:  # Any errors we can throw away. One of the intrusion set contains no aliases.
            pass

    # print(group_dict)

    #############################################################################################################################
    # At this point, we have the group_dict set up as a dictionary with all the unique group entries containing values of 0
    #############################################################################################################################

    twoD_list = generate_random_data(
        parse_csv.parse_attack_csv('mitreMatrix/layer.csv'))

    random_list = []
    for _ in range(0, 1500):
        rando = random.randint(0, len(twoD_list) - 1)
        random_list.append(twoD_list[rando])

    # Here is a list containing the random techniques we've generated
    # --> random_list

    # Now we create a list of how many times we see each technique.
    tech_dict = {}
    for item in random_list:
        if item not in tech_dict:
            tech_dict[item] = 0
        else:
            tech_dict[item] += 1

    """
    At this point we now have:
    - Dictionary with keys as group aliases and values all of int = 0
    - List of randomly generated events (the names)
    - A Dictionary called tech_dict which has the count of how many times a technique has been seen so far.

    The next step is to look at all the techniques we found, see which APT groups are associated with each
    technique, and adjust the counts in group_dict accordingly.

    We have to use this two step process because the querying command for STIXII is intensive. This two-step
    approach minimizes the process intensive task.
    """

    # x = get_technique_by_name(fs, 'Rundll32')
    for x in tech_dict.keys():
        temp_aliases = set()
        # print(x)
        y = get_technique_by_name(fs, x)
        tech_id = y[0].id
        # tech_id = 'attack-pattern--322bad5a-1c49-4d23-ab79-76d641794afa'
        # print(tech_id)
        for g in get_technique_users(fs, tech_id):
            # x will return a list of aliases.
            for y in g.aliases:
                # y is equal to an alias because it's coming from a list
                temp_aliases.add(y)

        # print(temp_aliases)
        for w in temp_aliases:
            print(group_dict)
            if w in group_dict:
                group_dict[w] += tech_dict[x]
            else:
                # This should not ever run. TODO: Add assert here to check for bugs.
                print("ERROR.")
                pass

        # exit()
    print(group_dict)


if __name__ == "__main__":
    main()
