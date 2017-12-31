dates <- c(rework_effort$Date)
debts <- c(rework_effort$Debt)
relation <- lm(debts~dates)
plot(dates, debts, pch = 16, cex = 1.3, col = "blue", main = "WORDPRESS TECHNICAL DEBT OVER TIME", xlab = "TIME (months from jan 2015 to dec 2017)", ylab = "TECHNICAL DEBT (man-year)")
abline(relation)
summary(relation)

maint_plug_data <- c(maint_plugins$Re)
barplot(sort(maint_plug_data), main = "LOC TO CHANGE PER PLUGIN FOR MAINTAINABILIY", ylab = "Lines of code to change (%)")
abline(h=0.5, col="red")

maint_them_data <- c(maint_themes$Re)
barplot(sort(maint_them_data), main = "LOC TO CHANGE PER THEMES FOR MAINTAINABILIY", ylab = "Lines of code to change (%)")
abline(h=0.5, col="red")

