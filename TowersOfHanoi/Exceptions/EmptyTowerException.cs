namespace TowersOfHanoi.Exceptions;
public class EmptyTowerException : Exception
{
    public EmptyTowerException() { }

    public EmptyTowerException(string message) : base(message) { }

    public EmptyTowerException(string message, Exception inner) : base(message, inner) { }
}
