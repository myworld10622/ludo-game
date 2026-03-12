const db = require('../../models')
var format = require('date-format');
const Sequelize = require('sequelize');
var moment = require('moment'); // require
var dateTime = require('node-datetime');
const BetModel = db.aviator
const UserModel = db.user
const GameModel = db.game
const setting_model = db.setting
const statement_model = db.statement

// 1. Betting
const addBet = async (req, res) => {
    if (!req.body.user_id || !req.body.amount || !req.body.game_id) {
        return res.status(200).send({ code: 100, message: "Please wait for next game" })
    }
    const user = await getUser(req.body.user_id)
    if (!user) {
        return res.status(200).send({ code: 404, message: "Invalid User" })
    }
    const game = await getGame(req.body.game_id);
    // console.log(game)
    if (!game) {
        return res.status(200).send({ code: 101, message: "Invalid Game Id" })
    }
    if (game.status != 0) {
        return res.status(200).send({ code: 101, message: "Invalid Bet" })
    }
    if (user.wallet < req.body.amount) {
        return res.status(200).send({ code: 102, message: "Insufficient Wallet Amount" })
    }
    var min_result = await minusAmount(req.body.amount, req.body.user_id, req.body.game_id, user)
    console.log("min_result - ",min_result);
    let info = {
        dragon_tiger_id: req.body.game_id,
        user_id: req.body.user_id,
        bet: 0,
        amount: req.body.amount,
        winning_amount: 0,
        user_amount: 0,
        comission_amount: 0,
        minus_bonus_wallet:min_result.bonus_wallet,
        minus_unutilized_wallet:min_result.unutilized_wallet,
        minus_winning_wallet:min_result.winning_wallet,
        added_date: format.asString(new Date()),
    }
    const result = await BetModel.create(info)
    if (result) {
        //Admin bucket
        let data = {
            aviator_bucket: Sequelize.literal('aviator_bucket+' + req.body.amount),
        }
        const a = await setting_model.update(data, { where: { id: 1 } })
        statementLog(req.body.user_id,'Aviator',-req.body.amount,result.id,0)
        return res.status(200).send({ result, code: 200, message: "Bet placed" })
    }
    return res.status(200).send({ code: 400, message: "Something went wrong" })

}


const userInfo = async (req, res) => {
    if (!req.body.user_id) {
        return res.status(200).send({ code: 100, message: "Invalid Parameter" })
    }
    const user = await getUser(req.body.user_id)
    if (!user) {
        return res.status(200).send({ code: 404, message: "Invalid User" })
    }
    res.status(200).send(user)

}


// 2. get all products

const getAllProducts = async (req, res) => {

    let products = await Product.findAll({})
    res.status(200).send(products)

}

// 3. get user
const getUser = (user_id) => {
    return UserModel.findOne({ where: { id: user_id } })
}

const getGame = (game_id) => {
    return GameModel.findOne({ where: { id: game_id }, order: [['id', 'DESC']], attributes: ['id', 'status', 'blast_time'], limit: 1 })
}

// get bet amount
const getBetAmount = (bet_id, user_id) => {
    return BetModel.findOne({ where: { id: bet_id, user_id: user_id } })
}


const getSetting = (admin_id) => {
    return setting_model.findOne({ where: { id: admin_id } })
}

// 4. minus amount after betting

const redeem = async (req, res) => {
    const setting = await getSetting(1)
    let multiplyAmount = req.body.amount;
    let user_id = req.body.user_id;
    let bet_id = req.body.bet_id;
    let game_id = req.body.game_id;
    var dt = dateTime.create();
    console.log("req.body ", req.body);
    var current_datetime = dt.format('Y-m-d H:M:S');
    if (!multiplyAmount || !user_id || !bet_id || !game_id) {
        return res.status(200).send({ code: 100, message: "Invalid Parameter" })
    }
    const ValidBet = await getBetAmount(bet_id, user_id);
    if (!ValidBet) {
        console.log("Invalid bet ", req.body);
        return res.status(200).send({ code: 101, message: "Invalid bet" })
    }
    if (ValidBet.winning_amount > 0) {
        console.log("Already Redeemed ", ValidBet);
        return res.status(200).send({ code: 101, message: "Already Redeemed" })
    }
    const game = await getGame(ValidBet.dragon_tiger_id);
    if (game.status != 1) {
        console.log("You can not redeem ", game);
        return res.status(200).send({ code: 101, message: "You can not redeem" })
    }
    // console.log("multiplyAmount ", multiplyAmount);
    // console.log("game.blast_time ", game.blast_time);
    if (multiplyAmount > parseFloat(game.blast_time)) {
        console.log("Invalid redeem amount ", game);
        return res.status(200).send({ code: 101, message: "Invalid redeem amount " })
    }

    // console.log("typeof multiplyAmount ", typeof multiplyAmount);
    // console.log("typeof game.blast_time ", typeof game.blast_time);

    let amount = ValidBet.amount * multiplyAmount;
    let admin_winning_amt = (amount * (setting.admin_commission / 100).toFixed(2));
    // let admin_winning_amt = 0;
    let user_winning_amt = (amount - admin_winning_amt).toFixed(2);

    let bet_info = {
        bet: multiplyAmount,
        winning_amount: amount,
        user_amount: user_winning_amt,
        comission_amount: admin_winning_amt,
    }
    BetModel.update(bet_info, { where: { id: bet_id } })
    // Sequelize.sync({ logging: console.log })

    let game_info = {
        winning_amount: Sequelize.literal('winning_amount+' + amount),
        user_amount: Sequelize.literal('user_amount+' + user_winning_amt),
        comission_amount: Sequelize.literal('comission_amount+' + admin_winning_amt),
    }
    GameModel.update(game_info, { where: { id: ValidBet.dragon_tiger_id } })
    let user_info = {
        wallet: Sequelize.literal('wallet+' + user_winning_amt),
        winning_wallet: Sequelize.literal('winning_wallet+' + user_winning_amt),
        // updated_date:format.asString(new Date()),
    }
    await UserModel.update(user_info, { where: { id: user_id } })
    let data = {
        aviator_bucket: Sequelize.literal('aviator_bucket-' + amount),
        admin_coin: Sequelize.literal('admin_coin+' + admin_winning_amt),
    }
    setting_model.update(data, { where: { id: 1 } })

    statementLog(user_id,'Aviator',user_winning_amt,bet_id,admin_winning_amt)

    return res.status(200).send({ code: 200, data: await getUser(user_id), user_winning_amt: user_winning_amt, admin_winning_amt: admin_winning_amt, message: "Redeemd success" })
}

const cancelBet = async (req, res) => {
    let bet_id = req.body.bet_id;
    var dt = dateTime.create();
    var current_datetime = dt.format('Y-m-d H:M:S');
    if (!bet_id || !req.body.user_id) {
        return res.status(200).send({ code: 100, message: "Invalid Parameter" })
    }
    const user = getUser(req.body.user_id)
    if (!user) {
        return res.status(200).send({ code: 404, message: "Invalid User" })
    }
    const ValidBet = await getBetAmount(bet_id, req.body.user_id);

    if (!ValidBet) {
        return res.status(200).send({ code: 101, message: "Invalid bet" })
    }
    console.log('game_id', ValidBet.dragon_tiger_id)
    const game = await getGame(ValidBet.dragon_tiger_id);
    // console.log('response', game)
    if (game && game.status != 0) {
        return res.status(200).send({ code: 101, message: "You can not cancel the bet" })
    }

    let amount = ValidBet.amount;
    let bet_info = {
        bet_status: 1,
    }
    BetModel.update(bet_info, { where: { id: bet_id } })
    let user_info = {
        wallet: Sequelize.literal('wallet+' + amount),
        unutilized_wallet: Sequelize.literal('unutilized_wallet+' + ValidBet.minus_unutilized_wallet),
        winning_wallet: Sequelize.literal('winning_wallet+' + ValidBet.minus_winning_wallet),
        bonus_wallet: Sequelize.literal('bonus_wallet+' + ValidBet.minus_winning_wallet),
    }
    await UserModel.update(user_info, { where: { id: req.body.user_id } })
    let data = {
        aviator_bucket: Sequelize.literal('aviator_bucket-' + amount),
    }

    statementLog(req.body.user_id,'Aviator Cancelled',amount,bet_id,0)

    setting_model.update(data, { where: { id: 1 } })
    return res.status(200).send({ code: 200, data: await getUser(req.body.user_id), message: "Cancelled success" })
}


// 4. minus amount after betting

const minusAmount = async (amount, user_id, room_id, user) => {
    let info = {
        wallet: Sequelize.literal('wallet-' + amount),
        aviator_room_id: room_id,
        updated_date: format.asString(new Date()),
    }
    UserModel.update(info, { where: { id: user_id } })
    var wallet_deduction_array = {unutilized_wallet: 0, winning_wallet: 0, bonus_wallet: 0};

    var unutilized_wallet = user.unutilized_wallet;
    var unutilized_wallet_minus = (unutilized_wallet > amount) ? amount : unutilized_wallet;
    amount -= unutilized_wallet_minus;
    if (unutilized_wallet_minus > 0) {
        let user_wallet = {
            unutilized_wallet: Sequelize.literal('unutilized_wallet-' + unutilized_wallet_minus),
        }
        UserModel.update(user_wallet, { where: { id: user_id } })
wallet_deduction_array.unutilized_wallet=unutilized_wallet_minus;
    }
    if (amount > 0) {
        var winning_wallet = user.winning_wallet;
        var winning_wallet_minus = (winning_wallet > amount) ? amount : winning_wallet;
        amount -= winning_wallet_minus;
        if (winning_wallet_minus > 0) {
            let user_wallet = {
                winning_wallet: Sequelize.literal('winning_wallet-' + winning_wallet_minus),
            }
            UserModel.update(user_wallet, { where: { id: user_id } })
            wallet_deduction_array.winning_wallet=winning_wallet_minus;
        }
    }

    if (amount > 0) {
        var bonus_wallet = user.bonus_wallet;
        var bonus_wallet_minus = (bonus_wallet > amount) ? amount : bonus_wallet;
        amount -= bonus_wallet_minus;
        if (bonus_wallet_minus > 0) {
            let user_wallet = {
                bonus_wallet: Sequelize.literal('bonus_wallet-' + bonus_wallet_minus),
            }
            UserModel.update(user_wallet, { where: { id: user_id } })
            wallet_deduction_array.bonus_wallet=bonus_wallet_minus;
        }
    }
    return wallet_deduction_array

}

const statementLog = async (user_id, source, amount, source_id=0,admin_commission=0) => {
    const user = await getUser(user_id)
    const setting = await getSetting(1)
    let data = {
        admin_coin: Sequelize.literal('admin_coin+' + admin_commission),
    }
    setting_model.update(data, { where: { id: 1 } })
    admin_current_wallet=setting.admin_coin+admin_commission;

    let info = {
        user_id: user_id,
        source: source,
        source_id: source_id,
        amount: amount,
        current_wallet:user.wallet,
        admin_commission: admin_commission,
        admin_coin:admin_current_wallet,
        added_date: format.asString(new Date()),
    }
    return statement_model.create(info)
}

// const minusAmount = (amount, user_id, room_id) => {
//     let info = {
//         wallet: amount,
//         aviator_room_id: room_id,
//         updated_date: format.asString(new Date()),
//     }
//     return UserModel.update(info, { where: { id: user_id } })
// }

// 4. update winning amount

const updateProduct = async (req, res) => {

    let id = req.params.id

    const product = await Product.update(req.body, { where: { id: id } })

    res.status(200).send(product)


}

// 5. delete product by id

const deleteProduct = async (req, res) => {

    let id = req.params.id

    await Product.destroy({ where: { id: id } })

    res.status(200).send('Product is deleted !')

}

// 6. get published product

const getPublishedProduct = async (req, res) => {

    const products = await Product.findAll({ where: { published: true } })

    res.status(200).send(products)

}

// 7. connect one to many relation Product and Reviews

const getProductReviews = async (req, res) => {

    const id = req.params.id

    const data = await Product.findOne({
        include: [{
            model: Review,
            as: 'review'
        }],
        where: { id: id }
    })

    res.status(200).send(data)

}

module.exports = {
    addBet,
    redeem,
    userInfo,
    cancelBet
}