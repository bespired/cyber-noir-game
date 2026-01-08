export const dialogs = [
    {
        "id": 1,
        "personage_id": 1,
        "titel": "Introductie Barman",
        "tree": {
            "nodes": {
                "info": {
                    "x": 360,
                    "y": 211,
                    "text": "Informatie is niet gratis vriend",
                    "options": [
                        {
                            "next": "[end]",
                            "text": "Laat maar",
                            "actions": [
                                {
                                    "type": "END TALK",
                                    "value": null
                                }
                            ]
                        },
                        {
                            "next": "hacker",
                            "text": "Hier heb je 50 credits",
                            "actions": [
                                {
                                    "type": "SET GAME TAG",
                                    "value": "sector  4"
                                }
                            ]
                        }
                    ]
                },
                "root": {
                    "x": 80,
                    "y": 41,
                    "text": "Wat kan ik voor je inschenken?",
                    "options": [
                        {
                            "next": "drankje",
                            "text": "Gewoon een drankje",
                            "actions": []
                        },
                        {
                            "next": "info",
                            "text": "Geef me informatie",
                            "actions": []
                        }
                    ]
                },
                "hacker": {
                    "x": 640,
                    "y": 240,
                    "text": "De hacker is in sector 4",
                    "options": [
                        {
                            "next": null,
                            "text": "Was nou zo moeilijk?",
                            "actions": [
                                {
                                    "type": "END TALK",
                                    "value": null
                                }
                            ]
                        }
                    ]
                },
                "drankje": {
                    "x": 364,
                    "y": 38,
                    "text": "Hier je drankje",
                    "options": [
                        {
                            "next": "[END]",
                            "text": "dank je",
                            "actions": [
                                {
                                    "type": "END TALK",
                                    "value": null
                                }
                            ]
                        }
                    ]
                }
            }
        },
        "is_active": true,
        "created_at": "2026-01-08T21:16:56.000000Z",
        "updated_at": "2026-01-08T21:16:56.000000Z",
        "personage": {
            "id": 1,
            "naam": "Elias Vane",
            "rol": "Antiquair",
            "beschrijving": "Eigenaar van een stoffige, donkere winkel vol pre-oorlogse analoge technologie. Spreekt traag en is obsessief nostalgisch.",
            "menselijke_status": "Lijdt aan chronische bloedarmoede, waardoor hij extreem bleek is. Toont echte haat tegen de Corporation.",
            "replicant_status": "Zijn obsessie met herinneringen is te perfect en feitelijk. Hij kan geen eigen jeugdherinneringen reproduceren.",
            "motief": "Zijn verzameling is een dekmantel om gevoelige informatie uit het verleden te verhandelen of te verbergen.",
            "is_replicant": true,
            "is_playable": false,
            "type": "persoon",
            "created_at": "2026-01-08T21:16:56.000000Z",
            "updated_at": "2026-01-08T21:16:56.000000Z"
        }
    },
    {
        "id": 2,
        "personage_id": 12,
        "titel": "sapper",
        "tree": {
            "nodes": {
                "root": {
                    "x": 70,
                    "y": 48,
                    "text": "Je staat op priv\u00e9terrein, agent.",
                    "options": [
                        {
                            "next": "question",
                            "text": "Ik zoek iemand",
                            "actions": []
                        },
                        {
                            "next": "threat",
                            "text": "Stap opzij",
                            "actions": []
                        }
                    ],
                    "nodeActions": [
                        {
                            "type": "GIVE CLUE",
                            "value": "7"
                        }
                    ]
                },
                "truth": {
                    "x": 1173,
                    "y": 39,
                    "text": "Lang geleden. Voor de blackout.",
                    "options": [
                        {
                            "next": "[END]",
                            "text": "Dat is genoeg",
                            "actions": [
                                {
                                    "type": "SET GAME TAG",
                                    "value": "sapper_suspect"
                                },
                                {
                                    "type": "END TALK",
                                    "value": null
                                }
                            ]
                        }
                    ]
                },
                "attack": {
                    "x": 1166,
                    "y": 450,
                    "text": "Dan eindigt het hier.",
                    "options": [
                        {
                            "next": "[END]",
                            "text": "\u2014",
                            "actions": [
                                {
                                    "type": "START COMBAT",
                                    "value": "sapper_morton"
                                }
                            ]
                        }
                    ]
                },
                "threat": {
                    "x": 335,
                    "y": 268,
                    "text": "Dat is geen manier om hier levend weg te gaan.",
                    "options": [
                        {
                            "next": "attack",
                            "text": "Trek je wapen",
                            "actions": []
                        }
                    ]
                },
                "protein": {
                    "x": 611,
                    "y": 37,
                    "text": "Prote\u00efneboerderijen. Iemand moet het doen.",
                    "options": [
                        {
                            "next": "records",
                            "text": "Ik controleer oude Nexus-8 records",
                            "actions": []
                        },
                        {
                            "next": "suspicious",
                            "text": "Je klinkt defensief",
                            "actions": []
                        }
                    ]
                },
                "records": {
                    "x": 880,
                    "y": 40,
                    "text": "Die tijd ligt achter me, agent.",
                    "options": [
                        {
                            "next": "truth",
                            "text": "Wanneer ben je geboren?",
                            "actions": []
                        }
                    ]
                },
                "question": {
                    "x": 340,
                    "y": 44,
                    "text": "Ik heb al lang niemand meer gezien.",
                    "options": [
                        {
                            "next": "protein",
                            "text": "Wat ben je hier aan het doen?",
                            "actions": []
                        },
                        {
                            "next": "[END]",
                            "text": "Dan laat ik je met rust",
                            "actions": [
                                {
                                    "type": "END TALK",
                                    "value": null
                                }
                            ]
                        }
                    ]
                },
                "suspicious": {
                    "x": 889,
                    "y": 232,
                    "text": "Je zou ook defensief zijn als je alleen wilde zijn.",
                    "options": [
                        {
                            "next": "attack",
                            "text": "Je bent een Nexus-8",
                            "actions": []
                        },
                        {
                            "next": "[END]",
                            "text": "Ik laat je gaan",
                            "actions": [
                                {
                                    "type": "END TALK",
                                    "value": null
                                }
                            ]
                        }
                    ]
                }
            }
        },
        "is_active": true,
        "created_at": "2026-01-08T21:16:56.000000Z",
        "updated_at": "2026-01-08T21:16:56.000000Z",
        "personage": {
            "id": 12,
            "naam": "Sapper",
            "rol": "verdachte",
            "beschrijving": "Openings replicant",
            "menselijke_status": null,
            "replicant_status": null,
            "motief": null,
            "is_replicant": true,
            "is_playable": false,
            "type": "persoon",
            "created_at": "2026-01-08T21:16:56.000000Z",
            "updated_at": "2026-01-08T21:16:56.000000Z"
        }
    },
    {
        "id": 3,
        "personage_id": 15,
        "titel": "Opening titels",
        "tree": {
            "nodes": []
        },
        "is_active": true,
        "created_at": "2026-01-08T21:16:56.000000Z",
        "updated_at": "2026-01-08T21:16:56.000000Z",
        "personage": {
            "id": 15,
            "naam": "Marianna",
            "rol": "verdachte",
            "beschrijving": "tbd",
            "menselijke_status": null,
            "replicant_status": null,
            "motief": null,
            "is_replicant": true,
            "is_playable": false,
            "type": "persoon",
            "created_at": "2026-01-08T21:16:56.000000Z",
            "updated_at": "2026-01-08T21:16:56.000000Z"
        }
    }
];
