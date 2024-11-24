using Spectre.Console;
using TowersOfHanoi.Enums;

namespace TowersOfHanoi;
public class Menu
{
    public void ShowMenu()
    {
        AnsiConsole.Write(
            new FigletText("TOWERS OF HANOI")
            .LeftJustified()
            .Color(Color.Red)
        );
        AnsiConsole.Write("1. Start a new game\n2. View Instructions\n3. Exit\n");
    }

    public MenuOptions GetMenuChoice()
    {
        return AnsiConsole.Prompt(
            new SelectionPrompt<MenuOptions>()
                .Title("Select an option")
                .AddChoices(Enum.GetValues<MenuOptions>())
        );
    }
}
