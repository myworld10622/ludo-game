const { Op } = require("sequelize");
const userService = require("./userService");
const errorHandler = require("../error/errorHandler");
const { BetIncomeMaster, BetIncomeLog, SalaryBonus, SalaryBonusLog, RebateBonus, DailyMissionRechargeBonusLog, DailyMissionRechargeBonus, AttendenceBonusLog, AttendenceBonus, user } = require("../models");
const adminService = require("./adminService");
const { getAmountByPercentageWithoutRound } = require("../utils/util");
var dateFormat = require('date-format');
const { UserWalletService } = require("./walletService");
const { GAMES, BASE_URL } = require("../constants");
const request = require("request");
const userWallet = new UserWalletService();
const rummyTournamentService = require("./rummyTournamentService");

class CronJobsService {
    constructor() {
        this.distributeDailyBetIncomeBonus = this.distributeDailyBetIncomeBonus.bind(this);
        this.handleDailyBonus = this.handleDailyBonus.bind(this);
        this.distributeDailySalaryBonus = this.distributeDailySalaryBonus.bind(this);
        this.distributeDailyReBetBonus = this.distributeDailyReBetBonus.bind(this);
        this.dailyMissionRechargeBonus = this.dailyMissionRechargeBonus.bind(this);
        this.dailyAttendenceBonus = this.dailyAttendenceBonus.bind(this);
    }
    async distributeDailyBetIncomeBonus(users) {
        try {
            // Get All users who have bet income greater then 0
            // const condition = { todays_bet: { [Op.gt]: 0 } }
            // const users = await userService.getByConditions(condition, ["id", "todays_bet", "todays_recharge"]);
            console.log("Daily Bet Bonus ===============", users.length)
            const betLevels = await BetIncomeMaster.findAll({
                attributes: ["id", "bonus"]
            });
            let totalAdminLoss = 0;
            for (let index = 0; index < users.length; index++) {
                let user = users[index];
                const userAmount = user.todays_bet;
                const userId = user.id;
                for (let j = 0; j < betLevels.length; j++) {
                    const bet = betLevels[j];
                    if (!bet.bonus) {
                        break;
                    }
                    if (!user.referred_by) {
                        break;
                    }

                    const referedUser = await userService.getByConditions({ id: user.referred_by }, ["id", "referred_by"]);

                    if (referedUser.length > 0) {
                        user = referedUser[0];
                        const bonus = await getAmountByPercentageWithoutRound(userAmount, bet.bonus);

                        const now = new Date();
                        const currentTimeBehore5Hour = await dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() - (5 * 60 * 60) * 1000));
                        const bonusPayload = {
                            bet_user_id: userId,
                            to_user_id: user.id,
                            bet_amount: userAmount,
                            bonus,
                            level: bet.id,
                            added_date: currentTimeBehore5Hour,
                            updated_date: currentTimeBehore5Hour
                        }

                        await BetIncomeLog.create(bonusPayload);
                        await userWallet.plusUserWallet(user.id, 0, bonus, 0, GAMES.betDailyBonus, 0, 0, 1, currentTimeBehore5Hour);

                        totalAdminLoss += parseFloat(bonus);
                    }
                }

                // await userService.update(userId, { todays_bet: 0 })
            }

            if (totalAdminLoss && totalAdminLoss > 0) {
                const now = new Date();
                const currentTimeBehore5Hour = await dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() - (5 * 60 * 60) * 1000));
                userWallet.directAdminProfitStatement(GAMES.betDailyBonus, totalAdminLoss * -1, currentTimeBehore5Hour)
            }
        } catch (error) {
            errorHandler.handle(error)
        }
    }

    async distributeDailyReBetBonus(users) {
        try {
            console.log("Daily Rebet Bonus ===============", users.length)
            const setting = await adminService.setting(["daily_rebate_income"]);
            const rebatePercent = setting.daily_rebate_income || 0;
            let totalAdminLoss = 0;
            for (let index = 0; index < users.length; index++) {
                let user = users[index];
                const userAmount = user.todays_bet;
                const userId = user.id;
                const bonus = await getAmountByPercentageWithoutRound(userAmount, rebatePercent);

                const now = new Date();
                const currentTimeBehore5Hour = await dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() - (5 * 60 * 60) * 1000));
                const bonusPayload = {
                    user_id: userId,
                    bet_amount: userAmount,
                    percentage: rebatePercent,
                    coin: bonus,
                    added_date: currentTimeBehore5Hour,
                    updated_date: currentTimeBehore5Hour
                }

                await RebateBonus.create(bonusPayload);
                await userWallet.plusUserWallet(userId, 0, bonus, 0, GAMES.dailyRebetBonus, 0, 0, 1, currentTimeBehore5Hour);

                totalAdminLoss += parseFloat(bonus);

                /*for (let j = 1; j <= 10; j++) {
                    const referedUser = await userService.getByConditions({ referred_by: user.id }, ["id"]);
                    if (referedUser.length > 0) {
                        user = referedUser[0];

                        const now = new Date();
                        const currentTimeBehore5Hour = await dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() - (5 * 60 * 60) * 1000));
                        const bonusPayload = {
                            user_id: userId,
                            bet_amount: userAmount,
                            percentage: 1,
                            coin: bonus,
                            added_date: currentTimeBehore5Hour,
                            updated_date: currentTimeBehore5Hour
                        }

                        await BetIncomeLog.create(bonusPayload);
                        await userWallet.plusUserWallet(userId, 0, bonus, 0, GAMES.dailyRebetBonus, 0, 0, 1, currentTimeBehore5Hour);

                        totalAdminLoss += parseFloat(bonus);
                    }
                }*/
            }

            if (totalAdminLoss && totalAdminLoss > 0) {
                const now = new Date();
                const currentTimeBehore5Hour = await dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() - (5 * 60 * 60) * 1000));
                userWallet.directAdminProfitStatement(GAMES.dailyRebetBonus, totalAdminLoss * -1, currentTimeBehore5Hour)
            }
        } catch (error) {
            errorHandler.handle(error)
        }
    }

    async dailyMissionRechargeBonus() {
        try {
            const condition = { todays_recharge: { [Op.gte]: 1000 } }
            const users = await userService.getByConditions(condition, ["id", "todays_bet", "todays_recharge"]);
            console.log("Daily Mission Recharge Bonus ===============", users.length)
            let totalAdminLoss = 0;
            for (let index = 0; index < users.length; index++) {
                let user = users[index];
                const userAmount = user.todays_recharge;
                const userId = user.id;

                const bonusData = await DailyMissionRechargeBonus.findOne({
                    where: {
                        recharge_amount: {
                            [Op.lte]: user.todays_recharge
                        }
                    },
                    order: [['recharge_amount', 'DESC']],
                    attributes: ["bonus", "recharge_amount"]
                });

                if (bonusData) {
                    const bonus = bonusData.bonus;

                    const now = new Date();
                    const currentTimeBehore5Hour = await dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() - (5 * 60 * 60) * 1000));
                    const bonusPayload = {
                        user_id: userId,
                        recharge: userAmount,
                        coin: bonus,
                        added_date: currentTimeBehore5Hour,
                        updated_date: currentTimeBehore5Hour
                    }

                    await DailyMissionRechargeBonusLog.create(bonusPayload);
                    await userWallet.plusUserWallet(userId, 0, bonus, 0, GAMES.dailyMissionRechargeBonus, 0, 0, 1, currentTimeBehore5Hour);

                    totalAdminLoss += parseFloat(bonus);
                }
            }

            if (totalAdminLoss && totalAdminLoss > 0) {
                const now = new Date();
                const currentTimeBehore5Hour = await dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() - (5 * 60 * 60) * 1000));
                userWallet.directAdminProfitStatement(GAMES.dailyMissionRechargeBonus, totalAdminLoss * -1, currentTimeBehore5Hour)
            }
        } catch (error) {
            errorHandler.handle(error)
        }
    }

    async dailyAttendenceBonus(users) {
        try {
            // const condition = { todays_recharge: { [Op.gte]: 1000 } }
            // const users = await userService.getByConditions(condition, ["id", "todays_bet"]);
            console.log("Daily Attendence Recharge Bonus ===============", users.length)
            let totalAdminLoss = 0;
            for (let index = 0; index < users.length; index++) {
                let user = users[index];
                const userAmount = user.todays_bet;
                const userId = user.id;

                const startOfYesterday = new Date();
                startOfYesterday.setDate(startOfYesterday.getDate() - 2);
                startOfYesterday.setHours(0, 0, 0, 0);

                const endOfYesterday = new Date();
                endOfYesterday.setDate(endOfYesterday.getDate() - 2);
                endOfYesterday.setHours(23, 59, 59, 999);

                const yesterdayAttendance = await AttendenceBonusLog.findOne({
                    where: {
                        added_date: {
                            [Op.between]: [startOfYesterday, endOfYesterday]
                        },
                        user_id: user.id
                    },
                    order: [['added_date', 'DESC']],
                    attributes: ["day", "id"]
                });

                let attendenceDay = 1;
                if (yesterdayAttendance) {
                    attendenceDay = yesterdayAttendance.day + 1;
                }

                const bonusData = await AttendenceBonus.findOne({
                    where: {
                        accumulated_amount: {
                            [Op.lte]: userAmount
                        },
                        id: attendenceDay
                    },
                    order: [["accumulated_amount", "DESC"]],
                    attributes: ["id", "accumulated_amount", "attendenece_bonus"]
                });

                if (bonusData) {
                    const bonus = bonusData.attendenece_bonus;

                    const now = new Date();
                    const currentTimeBehore5Hour = await dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() - (5 * 60 * 60) * 1000));
                    const bonusPayload = {
                        user_id: userId,
                        bet_amount: userAmount,
                        day: attendenceDay,
                        coin: bonus,
                        added_date: currentTimeBehore5Hour,
                        updated_date: currentTimeBehore5Hour
                    }

                    await AttendenceBonusLog.create(bonusPayload);
                    await userWallet.plusUserWallet(userId, 0, bonus, 0, GAMES.dailyAttendenceBonus, 0, 0, 1, currentTimeBehore5Hour);

                    totalAdminLoss += parseFloat(bonus);
                }
            }

            if (totalAdminLoss && totalAdminLoss > 0) {
                const now = new Date();
                const currentTimeBehore5Hour = await dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() - (5 * 60 * 60) * 1000));
                userWallet.directAdminProfitStatement(GAMES.dailyAttendenceBonus, totalAdminLoss * -1, currentTimeBehore5Hour)
            }
        } catch (error) {
            errorHandler.handle(error)
        }
    }

    async distributeDailySalaryBonus() {
        try {
            console.log("Daily Salary Bonus =========================")
            const users = await userService.getByConditions({}, ["id", "todays_bet", "todays_recharge"]);
            let totalAdminLoss = 0;
            for (let index = 0; index < users.length; index++) {
                let user = users[index];
                const directReferrelUsers = await userService.getByConditions({
                    referred_by: user.id,
                    todays_bet: {
                        [Op.gte]: 500
                    },
                    todays_recharge: {
                        [Op.gte]: 500
                    }
                }, ["id"]);
                const directReferrelUsersCount = directReferrelUsers.length;
                // if (directReferrelUsersCount > 2) {
                // Fetch active users less then or equal user count and take first in desc order
                const salaryBonus = await SalaryBonus.findOne({
                    where: {
                        active_users: {
                            [Op.lte]: directReferrelUsersCount
                        },
                        // max_bet: {
                        //     [Op.gte]: 500
                        // },
                    },
                    order: [['active_users', 'DESC']],
                    attributes: ["daily_salary_bonus", "id"]
                });

                if (salaryBonus) {
                    const bonus = salaryBonus.daily_salary_bonus;
                    const now = new Date();
                    const currentTimeBehore5Hour = await dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() - (5 * 60 * 60) * 1000));
                    const bonusPayload = {
                        user_id: user.id,
                        to_user_id: user.id,
                        bet_amount: 0,
                        bonus,
                        level: salaryBonus.id,
                        active_users: directReferrelUsersCount,
                        added_date: currentTimeBehore5Hour,
                        updated_date: currentTimeBehore5Hour
                    }

                    await SalaryBonusLog.create(bonusPayload);
                    await userWallet.plusUserWallet(user.id, 0, bonus, 0, GAMES.salaryDailyBonus, 0, 0, 1, currentTimeBehore5Hour);

                    totalAdminLoss += parseFloat(bonus);
                    // }
                }
            }

            if (totalAdminLoss && totalAdminLoss > 0) {
                const now = new Date();
                const currentTimeBehore5Hour = await dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() - (5 * 60 * 60) * 1000));
                userWallet.directAdminProfitStatement(GAMES.salaryDailyBonus, totalAdminLoss * -1, currentTimeBehore5Hour)
            }
        } catch (error) {
            errorHandler.handle(error)
        }
    }

    async handleDailyBonus() {
        console.log("Daily Bonus Cron Run Successfully =========")
        const condition = { todays_bet: { [Op.gt]: 0 } }
        const users = await userService.getByConditions(condition, ["id", "todays_bet", "todays_recharge", "referred_by"]);
        await this.distributeDailySalaryBonus();
        await this.dailyAttendenceBonus(users);
        await this.distributeDailyReBetBonus(users);
        await this.dailyMissionRechargeBonus();
        await this.distributeDailyBetIncomeBonus(users);

        await user.update(
            {
                todays_bet: 0,
                todays_recharge: 0
            },
            {
                where: {}
            });
    }

    checkPaymentStatus() {
        try {
            var options = {
                'method': 'GET',
                'url': BASE_URL + '/api/cron/check_payment_status'
            };
            request(options, function (error, response) {
                if (error) {
                    console.log(error)
                    throw new Error(error)
                }
            });
        } catch (error) {
            console.log(error)
        }
    }

    async startInFiveMinuteTournament(tournamentsArray, rummyTournamentSocket) {
        try {
            const tournaments = await rummyTournamentService.getTournementStartInFiveMinute();
            // console.log("---------------------",tournaments.length)
            if (tournaments.length > 0) {
                for (let index = 0; index < tournaments.length; index++) {
                    const tournament = tournaments[index].toJSON();
                    // console.log("-===================",tournament.id, tournamentsArray)
                    if (!tournamentsArray[tournament.id]) {
                        tournamentsArray[tournament.id] = tournament.id;
                        const roundStartTime = new Date(tournament.rounds[0].start_date);
                        const [hour, minute, second] = tournament.rounds[0].start_time.split(':').map(Number);
                        roundStartTime.setHours(hour, minute, second);

                        const now = new Date();
    
                        const diffInMs = roundStartTime.getTime() - now.getTime();
                        let timerInSecond = Math.round(diffInMs / 1000);
                        // let timerInSecond = 300;
                        // for (let tIndex = 0; tIndex < tournament.tables.length; tIndex++) {
                        //     const table = tournament.tables[tIndex];
                        //     console.log("YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY",table.id, table.tbl_rummy_tournament_table_user[0].user_id)
                        // }
                        console.log(JSON.stringify(tournament))
                        const intervalObj = setInterval(async() => {
                            rummyTournamentSocket.tournamentStartEvent(tournament.id, timerInSecond);
                            timerInSecond--;
                            if(timerInSecond <= 0) {
                                clearInterval(intervalObj);
                                delete tournamentsArray[tournament.id];
                                const startTournament = await rummyTournamentService.getStartTournamentById(tournament.id);
                                if(startTournament) {
                                    const tournamentItem = startTournament.toJSON();
                                    for (let tIndex = 0; tIndex < tournamentItem.tables.length; tIndex++) {
                                        const table = tournamentItem.tables[tIndex];
                                        await rummyTournamentSocket.startGameEvent(table.id, table.tbl_rummy_tournament_table_user[0].user_id, tournamentItem.id)
                                    }
                                }
                            }
                        }, 1000);
                    }
                }
            }
        } catch (error) {
            errorHandler.handle(error)
        }
    }
}

module.exports = new CronJobsService();