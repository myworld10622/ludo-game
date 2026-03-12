module.exports = (sequelize, DataTypes) => {
    const Teenpatti = sequelize.define("tbl_game", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        table_id: {
            type: DataTypes.INTEGER,
        },
        amount: {
            type: DataTypes.BIGINT,
        },
        winner_id: {
            type: DataTypes.INTEGER,
        },
        winner_type: {
            type: DataTypes.TINYINT,
            defaultValue: 0,
            comment: "0=User,1=Bot"
        },
        user_winning_amt: {
            type: DataTypes.FLOAT,
        },
        admin_winning_amt: {
            type: DataTypes.FLOAT
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
        tableName: 'tbl_game',
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

    Teenpatti.associate = (models) => {
        Teenpatti.belongsTo(models.TeenpattiTable, { foreignKey: 'table_id' });
    };

    return Teenpatti;
}