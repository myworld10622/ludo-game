module.exports = (sequelize, DataTypes) => {
    const RummyTournament = sequelize.define("tbl_rummy_tournament", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        tournament_id: {
            type: DataTypes.INTEGER,
        },
        table_id: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        joker: {
            type: DataTypes.STRING,
        },
        winner_id: {
            type: DataTypes.INTEGER,
            defaultValue: 0
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        },
        updated_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        },
        isDeleted: {
            type: DataTypes.TINYINT,
            allowNull: false,
            defaultValue: 0
        }
    }, {
        tableName: 'tbl_rummy_tournament',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date',
        defaultScope: {
            where: {
                isDeleted: 0
            }
        },
        scopes: {
            withDeleted: {
                where: {}
            }
        },
        indexes: [
            {
                name: 'idx_table_id',
                fields: ['table_id']
            },
            {
                name: 'idx_isDeleted',
                fields: ['isDeleted']
            }
        ]
    })

    return RummyTournament;
}